<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Evento de Stripe ya procesado: su event_id único hace idempotente el webhook. */
class StripeEvento extends Model
{
    protected $table = 'stripe_eventos';

    public $timestamps = false;

    protected $fillable = ['event_id', 'tipo', 'procesado_en'];

    protected $casts = ['procesado_en' => 'datetime'];
}
