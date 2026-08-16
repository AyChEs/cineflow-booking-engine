<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sesion extends Model
{
    use HasFactory;

    protected $table = 'sesions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fk_sala_id',
        'fk_pelicula_id',
        'fecha_hora',
        'preu_base',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'preu_base' => 'decimal:2',
        ];
    }

    /**
     * Cada sesión está en una sala
     */
    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class, 'fk_sala_id');
    }

    /**
     * Cada sesión es de una película
     */
    public function pelicula(): BelongsTo
    {
        return $this->belongsTo(Pelicula::class, 'fk_pelicula_id');
    }

    /**
     * Una sesión puede tener muchas reservas
     */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'fk_sesion_id');
    }
}
