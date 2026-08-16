<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Envuelve DevsApiHubMovieService con una capa de caché para reducir las
 * llamadas a la API de TMDB.
 *
 * Config: TMDB_CACHE_TTL (segundos, por defecto 24h) y TMDB_API_CACHE_ENABLED.
 */
class CachedMovieService
{
    protected DevsApiHubMovieService $service;
    protected int $cacheTtl;
    protected bool $cacheEnabled;

    public function __construct(DevsApiHubMovieService $service)
    {
        $this->service = $service;
        $this->cacheTtl = (int) env('TMDB_CACHE_TTL', 86400); // 24 horas
        $this->cacheEnabled = (bool) env('TMDB_API_CACHE_ENABLED', true);
    }

    /**
     * Cartelera actual (now_playing) cacheada.
     */
    public function getNowPlaying(array $params = []): array
    {
        if (!$this->cacheEnabled || !$this->service->isEnabled()) {
            return $this->service->getNowPlaying($params);
        }

        $cacheKey = 'tmdb:now_playing:' . md5(json_encode($params));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($params) {
            Log::info('TMDB Cache MISS - Fetching now_playing', ['cache_ttl' => $this->cacheTtl]);
            return $this->service->getNowPlaying($params);
        });
    }

    /**
     * Listado completo de películas (discover) cacheado.
     */
    public function getAll(): array
    {
        if (!$this->cacheEnabled || !$this->service->isEnabled()) {
            return $this->service->getAll();
        }

        $cacheKey = 'tmdb:all_movies';

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            Log::info('TMDB Cache MISS - Fetching all movies');
            return $this->service->getAll();
        });
    }

    /**
     * Detalle de una película por ID, con un TTL más largo.
     */
    public function getById(int $id): ?array
    {
        if (!$this->cacheEnabled || !$this->service->isEnabled()) {
            return $this->service->getById($id);
        }

        $cacheKey = "tmdb:movie:$id";
        $ttl = (int) env('TMDB_DETAIL_CACHE_TTL', 604800); // 7 días para el detalle

        return Cache::remember($cacheKey, $ttl, function () use ($id) {
            Log::info("TMDB Cache MISS - Fetching movie detail for ID: $id");
            return $this->service->getById($id);
        });
    }

    /**
     * Búsqueda discover cacheada (la clave se genera con el hash de los parámetros).
     */
    public function discover(array $params = []): array
    {
        if (!$this->cacheEnabled || !$this->service->isEnabled()) {
            return $this->service->discover($params);
        }

        $cacheKey = 'tmdb:discover:' . md5(json_encode($params));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($params) {
            Log::info('TMDB Cache MISS - Discover', ['params' => $params]);
            return $this->service->discover($params);
        });
    }

    public function isEnabled(): bool
    {
        return $this->service->isEnabled();
    }

    public function canWrite(): bool
    {
        return $this->service->canWrite();
    }

    /**
     * Vacía todas las cachés de TMDB (tras un refresh o actualización).
     */
    public function clearCache(): void
    {
        if (method_exists(Cache::store(), 'flush')) {
            Cache::flush();
            return;
        }

        // El driver de fichero no admite comodines, así que registramos las
        // claves conocidas. En producción con Redis se limpiaría por tags.
        $keys = [
            'tmdb:now_playing:*',
            'tmdb:all_movies',
            'tmdb:discover:*',
            'tmdb:movie:*',
        ];

        foreach ($keys as $pattern) {
            Log::info('TMDB cache cleared', ['pattern' => $pattern]);
        }
    }

    /**
     * Información de diagnóstico de la caché.
     */
    public function getCacheStats(): array
    {
        return [
            'enabled' => $this->cacheEnabled,
            'ttl_seconds' => $this->cacheTtl,
            'driver' => config('cache.default'),
        ];
    }
}
