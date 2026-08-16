<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
    ];

    /**
     * Relación muchos a muchos con Películas (tabla intermedia pelicula_categoria)
     */
    public function peliculas(): BelongsToMany
    {
        return $this->belongsToMany(
            Pelicula::class,
            'pelicula_categoria',
            'fk_categoria_id',
            'fk_pelicula_id'
        );
    }
}
