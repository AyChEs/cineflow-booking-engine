<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservaSeat extends Model
{
    use HasFactory;

    protected $table = 'reserva_seats';

    protected $fillable = [
        'reserva_id',
        'sesion_id',
        'butaca',
    ];

    /**
     * Timestamps: no los usamos en esta tabla
     */
    public $timestamps = false;

    /**
     * Relaciones
     */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(Sesion::class, 'sesion_id');
    }

    /**
     * Scopes útiles para queries optimizadas
     */
    public function scopeBySesion($query, $sesionId)
    {
        return $query->where('sesion_id', $sesionId);
    }

    public function scopeByButaca($query, $butaca)
    {
        return $query->where('butaca', $butaca);
    }

    public function scopeByReserva($query, $reservaId)
    {
        return $query->where('reserva_id', $reservaId);
    }
}
