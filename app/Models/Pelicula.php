<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Pelicula extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'sinopsis',
        'duracion_min',
        'classificacio_edad',
        'trailer_url',
        'poster_path',
        'tmdb_id',
        'rating',
    ];

    protected $appends = [
        'poster_url',
    ];

    /**
     * Una película puede tener muchas sesiones
     */
    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class, 'fk_pelicula_id');
    }

    /**
     * Relación muchos a muchos con Categorías (tabla intermedia pelicula_categoria)
     */
    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(
            Categoria::class,
            'pelicula_categoria',
            'fk_pelicula_id',
            'fk_categoria_id'
        );
    }

    /**
     * Acceso directo a las reservas de una película saltando la tabla de sesiones
     */
    public function reservas(): HasManyThrough
    {
        return $this->hasManyThrough(
            Reserva::class,
            Sesion::class,
            'fk_pelicula_id',
            'fk_sesion_id'
        );
    }

    public function getPosterUrlAttribute(): string
    {
        if ($this->poster_path) {
            // Rutas con esquema (http, https, data) - devolver como están
            if (Str::startsWith($this->poster_path, ['http://', 'https://', 'data:'])) {
                return $this->poster_path;
            }

            // Rutas relativas de TMDB (ej: /9M82HW1vFJrSGpNNQqwLVOr0FKm.jpg)
            if (Str::startsWith($this->poster_path, '/')) {
                $imageBase = config('services.movies_api.image_base_url', 'https://image.tmdb.org/t/p/w500');
                return rtrim($imageBase, '/') . $this->poster_path;
            }

            // Rutas locales en storage (ej: posters/filename.jpg)
            return asset($this->poster_path);
        }

        $title = trim((string) $this->titulo) ?: 'CineFlow';
        $initials = Str::upper(Str::substr(preg_replace('/\s+/', '', $title) ?? $title, 0, 2));
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeInitials = htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 1200" role="img" aria-label="{$safeTitle}">
    <defs>
        <linearGradient id="posterBg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#0f172a" />
            <stop offset="100%" stop-color="#7c2d12" />
        </linearGradient>
    </defs>
    <rect width="800" height="1200" fill="url(#posterBg)" rx="36" />
    <circle cx="400" cy="300" r="160" fill="rgba(255,255,255,0.08)" />
    <text x="400" y="360" text-anchor="middle" font-size="180" font-family="Arial, Helvetica, sans-serif" fill="#f8fafc" font-weight="700">{$safeInitials}</text>
    <text x="400" y="970" text-anchor="middle" font-size="48" font-family="Arial, Helvetica, sans-serif" fill="#e2e8f0" font-weight="700">{$safeTitle}</text>
    <text x="400" y="1040" text-anchor="middle" font-size="28" font-family="Arial, Helvetica, sans-serif" fill="#cbd5e1">Cartelera CineFlow</text>
</svg>
SVG;

        return 'data:image/svg+xml;charset=UTF-8,'.rawurlencode($svg);
    }
}
