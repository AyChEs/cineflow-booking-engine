<?php

namespace App\Http\Controllers;

use App\Models\CheckoutPago;
use App\Models\StripeEvento;
use App\Services\CheckoutService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Webhook de Stripe: la única vía que confirma una reserva pagada con Stripe.
 * Verifica la firma antes de leer nada y procesa cada evento una sola vez
 * (tabla stripe_eventos, event_id único, en la misma transacción).
 */
class StripeWebhookController extends Controller
{
    private const CONFIRMAN = ['checkout.session.completed', 'checkout.session.async_payment_succeeded'];

    private const CIERRAN = [
        'checkout.session.expired'              => CheckoutPago::CADUCADO,
        'checkout.session.async_payment_failed' => CheckoutPago::FALLIDO,
    ];

    public function __construct(private readonly CheckoutService $checkout)
    {
    }

    public function __invoke(Request $request): Response
    {
        $secreto = (string) config('services.stripe.webhook_secret');
        if ($secreto === '') {
            return response('', 400);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secreto,
            );
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            return response('', 400);
        }

        if (StripeEvento::where('event_id', $event->id)->exists()) {
            return response('', 200);
        }

        try {
            $despues = DB::transaction(function () use ($event) {
                StripeEvento::create(['event_id' => $event->id, 'tipo' => $event->type, 'procesado_en' => now()]);

                return $this->procesar($event);
            });
        } catch (UniqueConstraintViolationException $e) {
            // El mismo evento llegó dos veces a la vez: el otro ya lo procesa.
            return response('', 200);
        }

        if ($despues) {
            $despues();
        }

        return response('', 200);
    }

    private function procesar(\Stripe\Event $event): ?\Closure
    {
        $session = $event->data->object;
        $pagoId = (int) ($session->metadata->checkout_pago_id ?? 0);
        if ($pagoId === 0) {
            return null;
        }

        if (in_array($event->type, self::CONFIRMAN, true)) {
            // Con métodos de pago diferidos, «completed» llega sin cobrar: se espera al evento asíncrono.
            if ($event->type === 'checkout.session.completed' && $session->payment_status !== 'paid') {
                return null;
            }

            return $this->checkout->confirmar($pagoId, (string) $session->id, $session->payment_intent ?? null);
        }

        if (isset(self::CIERRAN[$event->type])) {
            $this->checkout->cerrar($pagoId, (string) $session->id, self::CIERRAN[$event->type]);

            return null;
        }

        Log::debug('Evento de Stripe ignorado', ['tipo' => $event->type]);

        return null;
    }
}
