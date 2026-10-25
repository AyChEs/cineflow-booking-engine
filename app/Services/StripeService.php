<?php

namespace App\Services;

/**
 * StripeService
 *
 * Decide si la compra usa Stripe Checkout (modo de prueba). Hacen falta la clave
 * secreta y el secreto del webhook: sin webhook no se podría confirmar ninguna
 * reserva, así que sin los dos la compra usa el pago simulado integrado.
 *
 * Tarjeta de prueba: 4242 4242 4242 4242 · cualquier fecha futura · cualquier CVC.
 */
class StripeService
{
    public function isEnabled(): bool
    {
        return ! empty(config('services.stripe.secret'))
            && ! empty(config('services.stripe.webhook_secret'));
    }
}
