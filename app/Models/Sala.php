<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Sala extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'capacidad',
        'disposicion_butacas',
        'fk_cine_id',
    ];

    /**
     * Cada sala pertenece a un cine
     */
    public function cine(): BelongsTo
    {
        return $this->belongsTo(Cine::class, 'fk_cine_id');
    }

    /**
     * Una sala puede tener muchas sesiones
     */
    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class, 'fk_sala_id');
    }

    /**
     * Acceso directo a las reservas de una sala saltando la tabla de sesiones
     */
    public function reservas(): HasManyThrough
    {
        return $this->hasManyThrough(
            Reserva::class,
            Sesion::class,
            'fk_sala_id',
            'fk_sesion_id'
        );
    }
}
