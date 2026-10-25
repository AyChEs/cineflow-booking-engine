<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Services\CartelSustituto;
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
        'fondo_path',
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

    /**
     * Imagen panorámica para la portada: el backdrop de TMDB (fondo_path), el fondo del cartel sustituto si existe
     * (public/carteles/*-fondo.webp); si no, el propio póster.
     */
    public function getFondoUrlAttribute(): string
    {
        if ($this->fondo_path) {
            return 'https://image.tmdb.org/t/p/w1280'.$this->fondo_path;
        }

        if ($this->poster_path && Str::startsWith($this->poster_path, 'carteles/')) {
            $fondo = Str::replaceLast('.webp', '-fondo.webp', $this->poster_path);
            if (is_file(public_path($fondo))) {
                return asset($fondo);
            }
        }

        return str_replace('/t/p/w500/', '/t/p/w780/', $this->poster_url);
    }

    /**
     * ¿La película tiene un fondo propio? Un backdrop de TMDB o el fondo de su cartel sustituto.
     * Si no, la portada y la ficha usan la sala de la marca (config('marca.fotos.sala')) en lugar de ampliar el póster.
     */
    public function getTieneFondoAttribute(): bool
    {
        if ($this->fondo_path) {
            return true;
        }

        return $this->poster_path
            && Str::startsWith($this->poster_path, 'carteles/')
            && is_file(public_path(Str::replaceLast('.webp', '-fondo.webp', $this->poster_path)));
    }

    /** srcset del fondo de TMDB (w780 y w1280) para no descargar la imagen grande en móvil; null si el fondo no es de TMDB. */
    public function getFondoSrcsetAttribute(): ?string
    {
        if (! $this->fondo_path) {
            return null;
        }
        $base = 'https://image.tmdb.org/t/p/';

        return $base.'w780'.$this->fondo_path.' 780w, '.$base.'w1280'.$this->fondo_path.' 1280w';
    }

    /**
     * Cartel de respaldo si el póster remoto no carga: el sustituto tipográfico
     * generado (public/carteles) o, si no hay, el SVG de la marca.
     */
    public function getCartelRespaldoUrlAttribute(): string
    {
        $base = CartelSustituto::CARPETA.'/'.$this->id.'-'.Str::slug($this->titulo ?: 'pelicula').'.webp';
        if (is_file(public_path($base))) {
            return asset($base);
        }

        return (clone $this)->forceFill(['poster_path' => null])->poster_url;
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
        <linearGradient id="posterBg" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#3A3641" />
            <stop offset="100%" stop-color="#0E0D10" />
        </linearGradient>
        <linearGradient id="haz" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#F2EEE6" stop-opacity=".22" />
            <stop offset="100%" stop-color="#F2EEE6" stop-opacity="0" />
        </linearGradient>
    </defs>
    <rect width="800" height="1200" fill="url(#posterBg)" />
    <path d="M600 0h16l170 1200H330z" fill="url(#haz)" />
    <text x="60" y="420" font-size="420" font-family="'Big Shoulders Display','Arial Narrow',Arial,sans-serif" font-weight="900" fill="none" stroke="#F2EEE6" stroke-opacity=".14" stroke-width="3">{$safeInitials}</text>
    <rect x="60" y="1010" width="96" height="8" fill="#E0284A" />
    <text x="60" y="960" font-size="64" font-family="'Big Shoulders Display','Arial Narrow',Arial,sans-serif" font-weight="900" fill="#F2EEE6">{$safeTitle}</text>
    <text x="60" y="1080" font-size="26" letter-spacing="4" font-family="'Big Shoulders Display','Arial Narrow',Arial,sans-serif" fill="#A8A3AE">EN CARTELERA EN CINEFLOW</text>
</svg>
SVG;

        return 'data:image/svg+xml;charset=UTF-8,'.rawurlencode($svg);
    }
}
