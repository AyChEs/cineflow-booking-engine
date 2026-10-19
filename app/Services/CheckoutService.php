<?php

namespace App\Services;

use App\Contracts\PasarelaPago;
use App\Exceptions\ButacaNoDisponibleException;
use App\Exceptions\SeatAlreadyReservedException;
use App\Http\Controllers\CompraController;
use App\Models\CheckoutPago;
use App\Models\ReservaSeat;
use App\Models\SeatLock;
use App\Models\Sesion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pago con Stripe Checkout. Todas las transiciones de CheckoutPago pasan por
 * aquí y solo actúan sobre un pago «abierto», con la fila bloqueada: así cada
 * evento repetido o tardío es inofensivo. La reserva la crea siempre
 * PurchaseService::confirmPurchase, llamado desde el webhook.
 */
class CheckoutService
{
    /** Minutos que vive la Checkout Session (el mínimo que admite Stripe). */
    public const MINUTOS_CHECKOUT = 30;

    /** Margen de los bloqueos sobre la caducidad de la Checkout Session. */
    public const MINUTOS_MARGEN_BLOQUEO = 2;

    public function __construct(
        private readonly PasarelaPago $pasarela,
        private readonly PurchaseService $purchase,
        private readonly EnvioEntrada $envio,
    ) {
    }

    /**
     * Crea el pago y la Checkout Session, y amplía los bloqueos de las butacas
     * mientras se paga. Devuelve el pago y la URL de Stripe.
     *
     * @param  array{sesion_id:int, entrades:array<string,int>, butaques:string[]}  $compra
     * @return array{0: CheckoutPago, 1: string}
     *
     * @throws ButacaNoDisponibleException
     */
    public function iniciar(array $compra, string $nombre, string $email, ?int $userId, string $sessionToken): array
    {
        $sesion = Sesion::with('pelicula')->findOrFail($compra['sesion_id']);
        $butacas = array_values($compra['butaques']);

        // Un pago abierto anterior de esta misma compra (volver atrás desde Stripe y pagar otra vez) se cierra.
        CheckoutPago::where('sesion_id', $sesion->id)
            ->where('estado', CheckoutPago::ABIERTO)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn ($q) => $q->whereNull('user_id')->where('session_token', $sessionToken))
            ->pluck('id')
            ->each(fn (int $id) => $this->cancelar($id));

        $this->comprobarButacas($sesion->id, $butacas, $userId, $sessionToken);

        [$lineas, $total] = $this->lineas($sesion, $compra['entrades']);
        $expiresAt = now()->addMinutes(self::MINUTOS_CHECKOUT);

        $pago = CheckoutPago::create([
            'sesion_id'     => $sesion->id,
            'butacas'       => $butacas,
            'tipus_entrada' => (string) array_search(max($compra['entrades']), $compra['entrades']),
            'entradas'      => $compra['entrades'],
            'total'         => $total,
            'email'         => $email,
            'nombre'        => $nombre,
            'user_id'       => $userId,
            'session_token' => $sessionToken,
            'estado'        => CheckoutPago::ABIERTO,
            'expires_at'    => $expiresAt,
        ]);

        try {
            $session = $this->pasarela->crearSesionCheckout([
                'mode'                => 'payment',
                'line_items'          => $lineas,
                'customer_email'      => $email,
                'client_reference_id' => (string) $pago->id,
                'metadata'            => ['checkout_pago_id' => (string) $pago->id, 'sesion_id' => (string) $sesion->id],
                'success_url'         => route('compra.stripe.return').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'          => route('compra.stripe.cancel'),
                'expires_at'          => $expiresAt->getTimestamp(),
            ], "checkout-pago-{$pago->id}");
        } catch (\Throwable $e) {
            $pago->update(['estado' => CheckoutPago::FALLIDO]);
            throw $e;
        }

        $pago->update(['stripe_session_id' => $session['id']]);

        // Las butacas siguen retenidas mientras la persona está en Stripe.
        $hasta = $expiresAt->copy()->addMinutes(self::MINUTOS_MARGEN_BLOQUEO);
        foreach ($butacas as $butaca) {
            SeatLock::updateOrCreate(
                ['sesion_id' => $sesion->id, 'butaca' => $butaca],
                ['user_id' => $userId, 'session_token' => $sessionToken, 'expires_at' => $hasta],
            );
        }

        return [$pago, $session['url']];
    }

    /**
     * checkout.session.completed (pagado) o async_payment_succeeded: confirma la
     * reserva. Si otra compra ganó la butaca, reembolsa. Devuelve una acción
     * para después del commit (enviar la entrada) o null.
     */
    public function confirmar(int $pagoId, string $stripeSessionId, ?string $paymentIntent): ?\Closure
    {
        $pago = CheckoutPago::lockForUpdate()->find($pagoId);
        if (! $pago || $pago->stripe_session_id !== $stripeSessionId) {
            Log::warning('Webhook de Stripe para un pago desconocido', ['checkout_pago_id' => $pagoId, 'stripe_session_id' => $stripeSessionId]);

            return null;
        }
        if (! $pago->estaAbierto()) {
            return null;
        }

        try {
            $reserva = $this->purchase->confirmPurchase(
                $pago->user_id,
                $pago->sesion_id,
                $pago->butacas,
                $pago->tipus_entrada,
                $pago->totalEuros(),
            );
        } catch (SeatAlreadyReservedException $e) {
            // Otra compra se llevó la butaca un instante antes: se devuelve el dinero.
            if ($paymentIntent) {
                $this->pasarela->reembolsar($paymentIntent, "reembolso-{$pago->id}");
            }
            $pago->update(['estado' => CheckoutPago::REEMBOLSADO]);
            $this->liberarButacas($pago);
            Log::channel('audit')->warning('Pago reembolsado: butaca ya vendida', [
                'checkout_pago_id' => $pago->id,
                'sesion_id'        => $pago->sesion_id,
                'butaca'           => $e->butaca,
                'payment_intent'   => $paymentIntent,
            ]);

            return null;
        }

        $pago->update(['estado' => CheckoutPago::PAGADO, 'reserva_id' => $reserva->id]);
        $this->liberarButacas($pago);

        return fn () => $this->envio->enviar($reserva, $pago->nombre, $pago->email, $pago->butacas, $pago->totalEuros());
    }

    /**
     * checkout.session.expired / async_payment_failed: cierra el pago y libera las
     * butacas. Sin $stripeSessionId (limpieza) no se comprueba la sesión.
     */
    public function cerrar(int $pagoId, ?string $stripeSessionId, string $estado): void
    {
        $pago = CheckoutPago::lockForUpdate()->find($pagoId);
        if (! $pago || ! $pago->estaAbierto()
            || ($stripeSessionId !== null && $pago->stripe_session_id !== $stripeSessionId)) {
            return;
        }

        $pago->update(['estado' => $estado]);
        $this->liberarButacas($pago);
    }

    /** La persona cancela en Stripe, o vuelve a pagar: expira la sesión y libera las butacas. */
    public function cancelar(int $pagoId): void
    {
        DB::transaction(function () use ($pagoId) {
            $pago = CheckoutPago::lockForUpdate()->find($pagoId);
            if (! $pago || ! $pago->estaAbierto()) {
                return;
            }

            if ($pago->stripe_session_id) {
                try {
                    $this->pasarela->expirarSesion($pago->stripe_session_id);
                } catch (\Throwable $e) {
                    // Ya no estaba abierta (pagada o caducada): el webhook dirá cuál.
                    Log::info('No se pudo expirar la sesión de Stripe', ['checkout_pago_id' => $pago->id, 'error' => $e->getMessage()]);
                }
            }

            $pago->update(['estado' => CheckoutPago::CADUCADO]);
            $this->liberarButacas($pago);
        });
    }

    /** Red de seguridad si un webhook no llega: pagos abiertos caducados hace más de 5 minutos. */
    public function limpiarCaducados(): int
    {
        $ids = CheckoutPago::where('estado', CheckoutPago::ABIERTO)
            ->where('expires_at', '<', now()->subMinutes(5))
            ->pluck('id');

        foreach ($ids as $id) {
            DB::transaction(fn () => $this->cerrar($id, null, CheckoutPago::CADUCADO));
        }

        return $ids->count();
    }

    /** Borra los bloqueos de las butacas de este pago (solo los de quien lo inició). */
    private function liberarButacas(CheckoutPago $pago): void
    {
        SeatLock::where('sesion_id', $pago->sesion_id)
            ->whereIn('butaca', $pago->butacas)
            ->when($pago->user_id, fn ($q) => $q->where('user_id', $pago->user_id))
            ->when(! $pago->user_id, fn ($q) => $q->where('session_token', $pago->session_token))
            ->delete();
    }

    /**
     * @param  string[]  $butacas
     *
     * @throws ButacaNoDisponibleException
     */
    private function comprobarButacas(int $sesionId, array $butacas, ?int $userId, string $sessionToken): void
    {
        SeatLock::clearExpired();

        foreach ($butacas as $butaca) {
            $vendida = ReservaSeat::where('sesion_id', $sesionId)->where('butaca', $butaca)->exists();
            if ($vendida || SeatLock::isLockedByOther($sesionId, $butaca, $userId, $sessionToken)) {
                throw new ButacaNoDisponibleException($butaca);
            }
        }
    }

    /**
     * Líneas de la Checkout Session y total, en céntimos, calculados en el servidor.
     *
     * @param  array<string,int>  $entradas
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function lineas(Sesion $sesion, array $entradas): array
    {
        $lineas = [];
        $total = 0;
        $titulo = $sesion->pelicula->titulo ?? 'CineFlow';

        foreach (CompraController::TIPUS as $tipo => $info) {
            $cantidad = (int) ($entradas[$tipo] ?? 0);
            if ($cantidad < 1) {
                continue;
            }
            $precio = CompraController::precioCentimos((float) $sesion->preu_base, $tipo);
            $total += $precio * $cantidad;
            $lineas[] = [
                'price_data' => [
                    'currency'     => config('services.stripe.currency', 'eur'),
                    'unit_amount'  => $precio,
                    'product_data' => ['name' => "{$titulo} · Entrada {$info['label']}"],
                ],
                'quantity' => $cantidad,
            ];
        }

        return [$lineas, $total];
    }
}
