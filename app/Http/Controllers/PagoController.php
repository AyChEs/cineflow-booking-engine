<?php

namespace App\Http\Controllers;

use App\Exceptions\ButacaNoDisponibleException;
use App\Models\CheckoutPago;
use App\Services\CheckoutService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Pago con Stripe Checkout desde el navegador. Ninguna de estas rutas confirma
 * una reserva: eso lo hace solo el webhook firmado (StripeWebhookController).
 */
class PagoController extends Controller
{
    public function __construct(private readonly CheckoutService $checkout)
    {
    }

    /** «Pagar con Stripe» en el paso 3: crea el pago y manda a Stripe. */
    public function checkout(Request $request): RedirectResponse
    {
        $compra = session('compra');
        if (! $compra || empty($compra['butaques'])) {
            return redirect()->route('peliculas.index');
        }
        if (! app(StripeService::class)->isEnabled()) {
            return redirect()->route('compra.step3');
        }

        $datos = $request->validate([
            'nom'   => 'required|string|max:100',
            'email' => 'required|email|max:150',
        ]);

        try {
            [$pago, $url] = $this->checkout->iniciar(
                $compra,
                $datos['nom'],
                $datos['email'],
                auth()->id(),
                session()->getId(),
            );
        } catch (ButacaNoDisponibleException $e) {
            return redirect()->route('compra.step2')
                ->with('error', "La butaca {$e->butaca} ya no está disponible. Vuelve a elegir tus butacas.");
        } catch (\Throwable $e) {
            Log::error('No se pudo crear la sesión de Stripe Checkout', ['error' => $e->getMessage()]);

            return redirect()->route('compra.step3')
                ->withInput($request->only('nom', 'email'))
                ->with('error', 'No se pudo iniciar el pago. Inténtalo de nuevo en unos segundos.');
        }

        session(['checkout_pago_id' => $pago->id]);

        return redirect()->away($url, 303);
    }

    /** Vuelta desde Stripe: solo muestra «Confirmando tu pago…»; la reserva llega por webhook. */
    public function vuelta(Request $request): View|RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');
        $pago = $sessionId !== '' ? CheckoutPago::where('stripe_session_id', $sessionId)->first() : null;

        if (! $pago) {
            return redirect()->route('peliculas.index');
        }
        abort_unless($pago->perteneceA(auth()->id(), session()->getId()), 403);

        return view('compra.procesando', ['pago' => $pago->load('sesion.pelicula')]);
    }

    /** Estado del pago para la página «Confirmando tu pago…». Solo para quien lo inició. */
    public function estado(CheckoutPago $checkoutPago): JsonResponse
    {
        abort_unless($checkoutPago->perteneceA(auth()->id(), session()->getId()), 403);

        $reservaUrl = null;
        if ($checkoutPago->estado === CheckoutPago::PAGADO) {
            session([
                'compra_confirmada' => [
                    'reserva_id' => $checkoutPago->reserva_id,
                    'nom'        => $checkoutPago->nombre,
                    'email'      => $checkoutPago->email,
                    'butaques'   => $checkoutPago->butacas,
                    'total'      => $checkoutPago->totalEuros(),
                ],
            ]);
            session()->forget(['compra', 'checkout_pago_id']);
            $reservaUrl = route('compra.confirmacio');
        }

        return response()->json(['estado' => $checkoutPago->estado, 'reserva_url' => $reservaUrl]);
    }

    /** «Volver» desde Stripe sin pagar: cierra el pago, libera las butacas y vuelve al paso 2. */
    public function cancelar(): RedirectResponse
    {
        $pago = CheckoutPago::find(session('checkout_pago_id'));
        if ($pago && $pago->perteneceA(auth()->id(), session()->getId())) {
            $this->checkout->cancelar($pago->id);
        }
        session()->forget('checkout_pago_id');

        $destino = session('compra') ? 'compra.step2' : 'peliculas.index';

        return redirect()->route($destino)->with('error', 'Has cancelado el pago. Tus butacas se han liberado; puedes volver a elegirlas.');
    }
}
