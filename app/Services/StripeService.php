<?php

namespace App\Services;

use Stripe\PaymentIntent;
use Stripe\StripeClient;

/**
 * StripeService
 *
 * Thin wrapper around Stripe Payment Intents running in test mode. The payment is
 * captured inline with the Stripe Payment Element (embedded on the checkout page),
 * so the customer never leaves the site. When no secret key is configured the
 * service reports itself as disabled and the purchase flow transparently falls
 * back to the built-in simulated card form.
 *
 * Test cards (Stripe test mode): 4242 4242 4242 4242 · any future expiry · any CVC.
 */
class StripeService
{
    public function isEnabled(): bool
    {
        return ! empty(config('services.stripe.secret'));
    }

    private function client(): StripeClient
    {
        return new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a PaymentIntent for the current cart and return it. The client_secret
     * is handed to the embedded Payment Element to confirm the payment inline.
     */
    public function createPaymentIntent(
        float $total,
        int $numEntrades,
        string $email,
        string $description,
        int $sesionId
    ): PaymentIntent {
        return $this->client()->paymentIntents->create([
            'amount' => (int) round($total * 100),
            'currency' => config('services.stripe.currency', 'eur'),
            'receipt_email' => $email,
            'description' => $description,
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'sesion_id' => (string) $sesionId,
                'num_entrades' => (string) $numEntrades,
            ],
        ]);
    }

    /**
     * Retrieve a PaymentIntent to confirm the payment status after confirmation.
     */
    public function retrievePaymentIntent(string $intentId): PaymentIntent
    {
        return $this->client()->paymentIntents->retrieve($intentId);
    }
}
