<?php

namespace App\Services;

use App\Contracts\PasarelaPago;
use Stripe\StripeClient;

/**
 * PasarelaPago con Stripe (modo de prueba). Toda escritura lleva clave de
 * idempotencia. El cliente se crea al primer uso: sin claves, construir los
 * controladores que dependen de la pasarela no falla.
 */
class StripePasarela implements PasarelaPago
{
    private ?StripeClient $client = null;

    public function __construct(private readonly string $secret)
    {
    }

    private function client(): StripeClient
    {
        return $this->client ??= new StripeClient($this->secret);
    }

    public function crearSesionCheckout(array $params, string $idempotencyKey): array
    {
        $session = $this->client()->checkout->sessions->create($params, ['idempotency_key' => $idempotencyKey]);

        return ['id' => $session->id, 'url' => $session->url];
    }

    public function expirarSesion(string $sessionId): void
    {
        $this->client()->checkout->sessions->expire($sessionId);
    }

    public function reembolsar(string $paymentIntentId, string $idempotencyKey): void
    {
        $this->client()->refunds->create(['payment_intent' => $paymentIntentId], ['idempotency_key' => $idempotencyKey]);
    }
}
