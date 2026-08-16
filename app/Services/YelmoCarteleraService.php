<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * YelmoCarteleraService
 *
 * Obtiene la cartelera real de Yelmo Cines directamente de su endpoint JSON de
 * "now playing". Devuelve la lista de títulos que Yelmo proyecta hoy en varias
 * ciudades, que luego emparejamos con TMDB para conseguir póster/valoración.
 *
 * Endpoint (método de página ASP.NET):
 *   POST https://www.yelmocines.es/now-playing.aspx/GetNowPlaying
 *   Body: {"cityKey":"madrid"}
 *   Respuesta: { d: { Cinemas: [ { Dates: [ { Movies: [ { Title, OriginalTitle } ] } ] } ] } }
 *
 * Es de solo lectura y se cachea 6h para ser respetuosos con su servidor.
 */
class YelmoCarteleraService
{
    private bool $enabled;
    private string $baseUrl;
    private int $timeout;
    /** @var string[] */
    private array $cities;

    public function __construct()
    {
        $this->enabled = (bool) config('services.yelmo.enabled', false);
        $this->baseUrl = rtrim((string) config('services.yelmo.base_url', 'https://www.yelmocines.es'), '/');
        $this->timeout = (int) config('services.yelmo.timeout', 10);
        $this->cities = (array) config('services.yelmo.cities', ['madrid']);
    }

    public function isEnabled(): bool
    {
        return $this->enabled && ! empty($this->cities);
    }

    /**
     * Lista de títulos únicos que Yelmo proyecta hoy en las ciudades configuradas.
     * Cada elemento: ['title' => string, 'original' => string|null].
     *
     * @return array<int, array{title: string, original: ?string}>
     */
    public function getTitles(): array
    {
        if (! $this->isEnabled()) {
            return Cache::get('yelmo_titles', []);
        }

        try {
            $seen = [];   // clave normalizada => true (para deduplicar)
            $titles = [];

            foreach ($this->cities as $city) {
                foreach ($this->fetchCityMovies($city) as $movie) {
                    $title = trim((string) ($movie['Title'] ?? ''));
                    if ($title === '') {
                        continue;
                    }

                    $key = mb_strtolower($title);
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;

                    $original = trim((string) ($movie['OriginalTitle'] ?? ''));
                    $titles[] = [
                        'title'    => $title,
                        'original' => $original !== '' ? $original : null,
                    ];
                }
            }

            if (! empty($titles)) {
                // Cache 6h: la cartelera de Yelmo cambia como mucho a diario.
                Cache::put('yelmo_titles', $titles, now()->addHours(6));
            }

            return $titles;
        } catch (\Throwable $e) {
            Log::warning('Yelmo cartelera failed, serving cached titles', ['error' => $e->getMessage()]);
            return Cache::get('yelmo_titles', []);
        }
    }

    /**
     * Descarga y aplana todas las películas de una ciudad (todos los cines y días).
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchCityMovies(string $city): array
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
                'User-Agent'   => 'Mozilla/5.0 (compatible; CineFlowBot/1.0)',
                'Accept'       => 'application/json, text/javascript, */*; q=0.01',
            ])
            ->post($this->baseUrl.'/now-playing.aspx/GetNowPlaying', [
                'cityKey' => $city,
            ]);

        if (! $response->ok()) {
            return [];
        }

        $json = $response->json();
        $cinemas = $json['d']['Cinemas'] ?? null;
        if (! is_array($cinemas)) {
            return [];
        }

        $movies = [];
        foreach ($cinemas as $cinema) {
            foreach ($cinema['Dates'] ?? [] as $date) {
                foreach ($date['Movies'] ?? [] as $movie) {
                    if (is_array($movie)) {
                        $movies[] = $movie;
                    }
                }
            }
        }

        return $movies;
    }
}
