<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un intento de pago con Stripe Checkout. Guarda lo necesario para crear la
 * reserva cuando llegue el webhook, sin depender de la sesión del navegador.
 */
class CheckoutPago extends Model
{
    public const ABIERTO = 'abierto';
    public const PAGADO = 'pagado';
    public const CADUCADO = 'caducado';
    public const FALLIDO = 'fallido';
    public const REEMBOLSADO = 'reembolsado';

    protected $table = 'checkout_pagos';

    protected $fillable = [
        'stripe_session_id',
        'sesion_id',
        'butacas',
        'tipus_entrada',
        'entradas',
        'total',
        'email',
        'nombre',
        'user_id',
        'session_token',
        'estado',
        'reserva_id',
        'expires_at',
    ];

    protected $casts = [
        'butacas'    => 'array',
        'entradas'   => 'array',
        'total'      => 'integer',
        'expires_at' => 'datetime',
    ];

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(Sesion::class, 'sesion_id');
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function estaAbierto(): bool
    {
        return $this->estado === self::ABIERTO;
    }

    /** Total en euros, como lo guarda la reserva. */
    public function totalEuros(): float
    {
        return round($this->total / 100, 2);
    }

    /** ¿Lo inició quien hace la petición? Usuario con sesión iniciada o, si no, la misma sesión del navegador. */
    public function perteneceA(?int $userId, string $sessionToken): bool
    {
        if ($this->user_id !== null) {
            return $userId !== null && $this->user_id === $userId;
        }

        return $this->session_token !== null && hash_equals($this->session_token, $sessionToken);
    }
}
