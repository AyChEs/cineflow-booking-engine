<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * StripeService
 *
 * Decide si la compra usa Stripe Checkout (modo de prueba). Hacen falta la clave
 * secreta y el secreto del webhook: sin webhook no se podría confirmar ninguna
 * reserva, así que sin los dos la compra usa el pago simulado integrado.
 *
 * Guarda de dinero real: este proyecto solo cobra en modo de prueba. Si STRIPE_KEY o
 * STRIPE_SECRET son claves «live» (pk_live_, sk_live_, rk_live_) y no existe
 * STRIPE_PERMITIR_LIVE=true, Stripe queda desactivado: se vacían las claves de la
 * configuración (así tampoco el webhook ni los reembolsos las usan), la compra usa el
 * pago simulado y se escribe un aviso en el log.
 *
 * Tarjeta de prueba: 4242 4242 4242 4242 · cualquier fecha futura · cualquier CVC.
 */
class StripeService
{
    /** Prefijos de las claves que mueven dinero real. */
    public const PREFIJOS_LIVE = ['sk_live_', 'rk_live_', 'pk_live_'];

    public function isEnabled(): bool
    {
        return ! $this->liveBloqueado()
            && ! empty(config('services.stripe.secret'))
            && ! empty(config('services.stripe.webhook_secret'));
    }

    /** ¿Alguna de las claves configuradas es «live»? */
    public function hayClaveLive(): bool
    {
        foreach ([config('services.stripe.key'), config('services.stripe.secret')] as $clave) {
            $clave = is_string($clave) ? trim($clave) : '';
            foreach (self::PREFIJOS_LIVE as $prefijo) {
                if ($clave !== '' && str_starts_with($clave, $prefijo)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** ¿Hay una clave «live» sin el permiso explícito STRIPE_PERMITIR_LIVE=true? */
    public function liveBloqueado(): bool
    {
        return $this->hayClaveLive()
            && ! filter_var(config('services.stripe.permitir_live'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Aplica la guarda: si hay una clave «live» sin permiso, vacía las claves de Stripe de la
     * configuración y avisa en el log (como mucho una vez cada 10 minutos). Devuelve si bloqueó.
     */
    public function aplicarGuarda(): bool
    {
        if (! $this->liveBloqueado()) {
            return false;
        }

        config([
            'services.stripe.key'            => null,
            'services.stripe.secret'         => null,
            'services.stripe.webhook_secret' => null,
        ]);

        $aviso = 'Stripe DESACTIVADO: se han configurado claves «live» (pk_live_, sk_live_ o rk_live_) y esta app solo cobra en modo de prueba. '
            .'Se usa el pago simulado y no se cobra nada. Usa claves sk_test_/pk_test_ o, si de verdad quieres cobrar dinero real, define STRIPE_PERMITIR_LIVE=true.';
        try {
            if (Cache::add('stripe-live-bloqueado', true, now()->addMinutes(10))) {
                Log::warning($aviso);
            }
        } catch (\Throwable) {
            Log::warning($aviso); // sin caché disponible: se avisa igualmente
        }

        return true;
    }
}
