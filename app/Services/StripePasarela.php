<?php

namespace App\Services;

use App\Contracts\PasarelaPago;
use Stripe\StripeClient;

/** PasarelaPago con Stripe (modo de prueba). Toda escritura lleva clave de idempotencia. */
class StripePasarela implements PasarelaPago
{
    public function __construct(private readonly StripeClient $client)
    {
    }

    public function crearSesionCheckout(array $params, string $idempotencyKey): array
    {
        $session = $this->client->checkout->sessions->create($params, ['idempotency_key' => $idempotencyKey]);

        return ['id' => $session->id, 'url' => $session->url];
    }

    public function expirarSesion(string $sessionId): void
    {
        $this->client->checkout->sessions->expire($sessionId);
    }

    public function reembolsar(string $paymentIntentId, string $idempotencyKey): void
    {
        $this->client->refunds->create(['payment_intent' => $paymentIntentId], ['idempotency_key' => $idempotencyKey]);
    }
}
