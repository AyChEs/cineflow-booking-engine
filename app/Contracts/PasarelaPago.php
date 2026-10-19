<?php

namespace App\Contracts;

/**
 * Lo que la compra necesita de la pasarela de pago. La implementación real es
 * StripePasarela; las pruebas usan un doble sin red.
 */
interface PasarelaPago
{
    /**
     * Crea una sesión de Checkout y devuelve ['id' => string, 'url' => string].
     *
     * @param  array<string, mixed>  $params  parámetros de la Checkout Session
     * @return array{id: string, url: string}
     */
    public function crearSesionCheckout(array $params, string $idempotencyKey): array;

    /** Expira una sesión de Checkout. Lanza una excepción si ya no está abierta. */
    public function expirarSesion(string $sessionId): void;

    /** Reembolsa por completo un cobro. */
    public function reembolsar(string $paymentIntentId, string $idempotencyKey): void;
}
