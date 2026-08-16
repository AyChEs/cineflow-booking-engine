<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cine extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'direccion_completa',
        'ciudad',
        'provincia',
    ];

    /**
     * Un cine tiene muchas salas
     */
    public function salas(): HasMany
    {
        return $this->hasMany(Sala::class, 'fk_cine_id');
    }
}
