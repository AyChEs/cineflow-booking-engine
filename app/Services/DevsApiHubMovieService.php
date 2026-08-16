<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DevsApiHubMovieService
{
    /**
     * Umbrales del filtro "cartelera comercial".
     *
     * now_playing?region=ES devuelve muchos estrenos limitados, de festival o
     * extranjeros que las grandes cadenas (Ocine/Yelmo) no proyectan. El análisis
     * de la distribución de popularidad muestra un corte limpio: los títulos
     * comerciales se agrupan en popularidad alta y el ruido queda por debajo.
     */
    private const NOW_PLAYING_MIN_POPULARITY = 25;
    private const NOW_PLAYING_MIN_VOTES = 150;
    private const NOW_PLAYING_MAX = 30;

    private string $baseUrl;
    private bool $enabled;
    private int $timeout;
    private string $apiKey;
    private string $language;
    private string $imageBase;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.movies_api.base_url', 'https://api.themoviedb.org/3'), '/');
        $this->enabled = (bool) config('services.movies_api.enabled', true);
        $this->timeout = (int) config('services.movies_api.timeout', 8);
        $this->apiKey = (string) config('services.movies_api.api_key', '');
        $this->language = (string) config('services.movies_api.language', 'es-ES');
        $this->imageBase = rtrim((string) config('services.movies_api.image_base_url', 'https://image.tmdb.org/t/p/w500'), '/');
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->apiKey !== '';
    }

    public function canWrite(): bool
    {
        // En esta integración usamos TMDB solo como catálogo externo de lectura.
        return false;
    }

    public function getAll(): array
    {
        if (! $this->isEnabled()) {
            return Cache::get('tmdb_all', []);
        }

        try {
            // TMDB pagina resultados; agregamos varias páginas para tener más cartelera.
            $all = [];
            foreach ([1, 2, 3] as $page) {
                $chunk = $this->discover(['page' => $page]);
                if (empty($chunk)) {
                    break;
                }
                $all = array_merge($all, $chunk);
            }

            // Guardamos en caché 1h para servir como fallback si TMDB cae
            if (! empty($all)) {
                Cache::put('tmdb_all', $all, now()->addHour());
            }

            return $all;
        } catch (\Throwable $e) {
            Log::warning('TMDB getAll failed, serving cached data', ['error' => $e->getMessage()]);
            return Cache::get('tmdb_all', []);
        }
    }

   public function getById(int $id): ?array
{
    if (! $this->isEnabled()) {
        return null;
    }

    // El endpoint de detalle devuelve runtime y géneros completos (no solo IDs)
    $movie = $this->requestSingle('movie/'.$id);
    if (! $movie || empty($movie['id']) || empty($movie['title'])) {
        return null;
    }

    $normalized = $this->normalizeMovie($movie);
    if (! $normalized) {
        return null;
    }

    // Runtime — solo disponible en el detalle, no en discover/search
    if (isset($movie['runtime']) && (int) $movie['runtime'] > 0) {
        $normalized['runtime'] = (int) $movie['runtime'];
    }

    // Nombres de géneros para marcar checkboxes en el formulario de admin
    if (! empty($movie['genres']) && is_array($movie['genres'])) {
        $normalized['genre_names'] = array_values(
            array_filter(array_column($movie['genres'], 'name'))
        );
    }

    // Certificación de edad para España (endpoint release_dates)
    try {
        $relDates = $this->requestCollection("movie/{$id}/release_dates");
        foreach ($relDates['results'] ?? [] as $country) {
            if (($country['iso_3166_1'] ?? '') === 'ES') {
                foreach ($country['release_dates'] ?? [] as $rd) {
                    if (! empty($rd['certification'])) {
                        $normalized['certification'] = $rd['certification'];
                        break 2;
                    }
                }
            }
        }
    } catch (\Throwable $e) {
        // La certificación es un dato opcional; si falla no bloqueamos
    }

    return $normalized;
}

    public function getByLimit(int $limit): array
    {
        $limit = max(1, $limit);
        $movies = $this->discover(['page' => 1]);

        return array_slice($movies, 0, $limit);
    }

    public function getByGenre(string $genre): array
    {
        $genreId = $this->resolveGenreId($genre);
        if (! $genreId) {
            return [];
        }

        return $this->discover(['with_genres' => $genreId]);
    }

    public function getByStars(string $stars): array
    {
        $fiveStars = (float) $stars;
        $tmdbVote = max(0.0, min(10.0, $fiveStars * 2));

        return $this->discover([
            'vote_average.gte' => number_format($tmdbVote, 1, '.', ''),
            'vote_count.gte' => 50,
        ]);
    }

    public function getByYear(int $year): array
    {
        return $this->discover([
            'primary_release_year' => $year,
        ]);
    }

    /**
     * Busca películas en TMDB por texto libre.
     * Se usa en el widget de creación de películas del panel de admin.
     */
    public function searchByQuery(string $query): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $cacheKey = 'tmdb_search_' . md5($query);

        try {
            $payload = $this->requestCollection('search/movie', [
                'query'  => $query,
                'region' => 'ES',
            ]);

            if (empty($payload['results']) || ! is_array($payload['results'])) {
                return Cache::get($cacheKey, []);
            }

            $results = array_values(array_slice(
                $this->normalizeMovies($payload['results']),
                0, 8
            ));

            // Cache búsquedas 30 min para fallback y reducir llamadas
            Cache::put($cacheKey, $results, now()->addMinutes(30));

            return $results;
        } catch (\Throwable $e) {
            Log::warning('TMDB search request failed, serving cache', ['error' => $e->getMessage()]);
            return Cache::get($cacheKey, []);
        }
    }

    /**
     * Empareja un título de cartelera (p. ej. de Yelmo) con la película de TMDB
     * más probable, para enriquecerlo con póster, géneros y valoración.
     * Prefiere una coincidencia exacta de título (o título original) y, si no la
     * hay, cae al primer resultado de búsqueda de TMDB.
     */
    public function matchByTitle(string $title, ?string $originalTitle = null): ?array
    {
        $title = trim($title);
        if ($title === '' || ! $this->isEnabled()) {
            return null;
        }

        $results = $this->searchByQuery($title);
        if (empty($results) && $originalTitle) {
            $results = $this->searchByQuery($originalTitle);
        }
        if (empty($results)) {
            return null;
        }

        $wanted = [mb_strtolower($title)];
        if ($originalTitle) {
            $wanted[] = mb_strtolower(trim($originalTitle));
        }

        foreach ($results as $movie) {
            if (in_array(mb_strtolower((string) ($movie['title'] ?? '')), $wanted, true)) {
                return $movie;
            }
        }

        return $results[0];
    }

    /**
     * Obtiene las películas que se están proyectando ahora en España.
     * Se usa para el comando de generación automática de sesiones.
     */
    public function getNowPlaying(): array
    {
        if (! $this->isEnabled()) {
            // Sin API key, servimos la última respuesta cacheada si existe
            return Cache::get('tmdb_now_playing', []);
        }

        try {
            $all        = [];
            $maxPages   = 3; // ~60 estrenos; páginas más allá son cine indie/local con poco interés

            for ($page = 1; $page <= $maxPages; $page++) {
                $payload = $this->requestCollection('movie/now_playing', [
                    'region' => 'ES',
                    'page'   => $page,
                ]);

                if (empty($payload['results']) || ! is_array($payload['results'])) {
                    break;
                }

                $all = array_merge($all, $payload['results']);

                $totalPages = (int) ($payload['total_pages'] ?? 1);
                if ($page >= $totalPages) {
                    break;
                }
            }

            // Filtro de cartelera comercial. now_playing?region=ES incluye mucho
            // estreno limitado, de festival o extranjero que las grandes cadenas
            // no proyectan. Nos quedamos solo con los estrenos de alcance
            // comercial: deben tener póster y superar un umbral de popularidad y
            // votos. Ordenamos por popularidad y limitamos a un tamaño realista.
            $comerciales = array_filter($all, function ($m) {
                if (empty($m['poster_path'])) {
                    return false;
                }
                if (! empty($m['adult'])) {
                    return false;
                }
                $popularidad = (float) ($m['popularity'] ?? 0);
                $votos       = (int) ($m['vote_count'] ?? 0);

                return $popularidad >= self::NOW_PLAYING_MIN_POPULARITY
                    || $votos >= self::NOW_PLAYING_MIN_VOTES;
            });

            usort($comerciales, fn($a, $b) =>
                (float) ($b['popularity'] ?? 0) <=> (float) ($a['popularity'] ?? 0)
            );

            $comerciales = array_slice($comerciales, 0, self::NOW_PLAYING_MAX);

            $normalized = $this->normalizeMovies($comerciales);

            // Cacheamos la respuesta válida durante 1 hora como fallback
            // Si TMDB cae, la cartelera sigue mostrando los últimos datos
            if (! empty($normalized)) {
                Cache::put('tmdb_now_playing', $normalized, now()->addHour());
            }

            return $normalized;
        } catch (\Throwable $e) {
            Log::warning('TMDB now_playing failed, serving cached data', [
                'error' => $e->getMessage(),
            ]);
            // Fallback: devolvemos los últimos datos válidos cacheados
            return Cache::get('tmdb_now_playing', []);
        }
    }

    public function createMovie(array $payload): ?array
    {
        return null;
    }

    public function updateMovie(int $id, array $payload): ?array
    {
        return null;
    }

    public function deleteMovie(int $id): bool
    {
        return false;
    }

    private function discover(array $extraParams = []): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        try {
            // Endpoint principal para búsqueda/filtrado de películas en TMDB.
            $payload = $this->requestCollection('discover/movie', array_merge([
                'include_adult' => 'false',
                'include_video' => 'false',
                'sort_by' => 'popularity.desc',
            ], $extraParams));

            if (empty($payload['results']) || ! is_array($payload['results'])) {
                return [];
            }

            return $this->normalizeMovies($payload['results']);
        } catch (\Throwable $e) {
            Log::warning('TMDB discover request failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function resolveGenreId(string $genre): ?int
    {
        $genre = trim(mb_strtolower($genre));
        if ($genre === '') {
            return null;
        }

        // TMDB filtra por ID de género, por eso resolvemos el nombre primero.
        $payload = $this->requestCollection('genre/movie/list');
        $genres = $payload['genres'] ?? [];

        foreach ($genres as $item) {
            if (! is_array($item) || ! isset($item['id'], $item['name'])) {
                continue;
            }

            if (mb_strtolower((string) $item['name']) === $genre) {
                return (int) $item['id'];
            }
        }

        return null;
    }

    private function requestCollection(string $path, array $params = []): array
    {
        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->get($this->baseUrl.'/'.ltrim($path, '/'), array_merge([
                'api_key' => $this->apiKey,
                'language' => $this->language,
            ], $params));

        if (! $response->ok()) {
            return [];
        }

        $json = $response->json();
        return is_array($json) ? $json : [];
    }

    private function requestSingle(string $path): ?array
    {
        $json = $this->requestCollection($path);
        return ! empty($json) ? $json : null;
    }

    private function normalizeMovies(array $items): array
    {
        return array_values(array_filter(array_map(fn($movie) => $this->normalizeMovie($movie), $items)));
    }

    private function normalizeMovie($movie): ?array
    {
        if (! is_array($movie) || empty($movie['id']) || empty($movie['title'])) {
            return null;
        }

        // Extraemos el año desde release_date para mostrarlo en filtros y detalle.
        $year = null;
        if (! empty($movie['release_date']) && preg_match('/^(\d{4})-/', (string) $movie['release_date'], $m)) {
            $year = (int) $m[1];
        }

        $genres = '';
        if (! empty($movie['genres']) && is_array($movie['genres'])) {
            $names = array_values(array_filter(array_map(
                fn($g) => is_array($g) && ! empty($g['name']) ? (string) $g['name'] : null,
                $movie['genres']
            )));
            $genres = implode(', ', $names);
        }

        if ($genres === '' && ! empty($movie['genre_ids']) && is_array($movie['genre_ids'])) {
            // Fallback cuando TMDB solo devuelve IDs y no nombres de género.
            $genres = implode(', ', array_map(fn($id) => '#'.$id, $movie['genre_ids']));
        }

        $posterPath = (string) ($movie['poster_path'] ?? '');
        $imageUrl = $posterPath !== '' ? $this->imageBase.$posterPath : '';

        $genreIds = [];
        if (!empty($movie['genre_ids']) && is_array($movie['genre_ids'])) {
            $genreIds = array_values(array_map('intval', $movie['genre_ids']));
        } elseif (!empty($movie['genres']) && is_array($movie['genres'])) {
            $genreIds = array_values(array_filter(array_map(
                fn($g) => is_array($g) && isset($g['id']) ? (int) $g['id'] : null,
                $movie['genres']
            )));
        }

        return [
            'id' => (int) $movie['id'],
            'title' => (string) $movie['title'],
            'description' => (string) ($movie['overview'] ?? ''),
            'year' => $year,
            'image_url' => $imageUrl,
            'genre' => $genres,
            'genre_ids' => $genreIds,
            'stars' => isset($movie['vote_average']) ? round(((float) $movie['vote_average']) / 2, 1) : null,
        ];
    }
}
