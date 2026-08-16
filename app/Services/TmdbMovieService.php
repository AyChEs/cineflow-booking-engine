<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class TmdbMovieService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.tmdb.enabled') && filled(config('services.tmdb.api_key'));
    }

    public function searchMovies(string $query): array
    {
        if (! $this->isEnabled() || trim($query) === '') {
            return [];
        }

        $response = Http::timeout(10)
            ->get($this->endpoint('/search/movie'), $this->baseParams() + [
                'query' => $query,
                'include_adult' => false,
                'page' => 1,
            ]);

        if (! $response->ok()) {
            return [];
        }

        return collect($response->json('results', []))
            ->take(10)
            ->map(fn(array $movie) => $this->transformSearchResult($movie))
            ->filter()
            ->values()
            ->all();
    }

    public function getMovieDetails(string $tmdbId): ?array
    {
        if (! $this->isEnabled() || trim($tmdbId) === '') {
            return null;
        }

        $response = Http::timeout(10)
            ->get($this->endpoint('/movie/'.$tmdbId), $this->baseParams() + [
                'append_to_response' => 'videos',
            ]);

        if (! $response->ok()) {
            return null;
        }

        $movie = $response->json();
        $trailerUrl = $this->extractYoutubeTrailer($movie);

        return [
            'id' => (string) Arr::get($movie, 'id'),
            'source' => 'tmdb',
            'titulo' => Arr::get($movie, 'title'),
            'sinopsis' => Arr::get($movie, 'overview'),
            'duracion_min' => Arr::get($movie, 'runtime') ?: null,
            'classificacio_edad' => null,
            'trailer_url' => $trailerUrl,
            'poster_url' => $this->makePosterUrl(Arr::get($movie, 'poster_path')),
            'release_date' => Arr::get($movie, 'release_date'),
        ];
    }

    private function transformSearchResult(array $movie): ?array
    {
        $id = Arr::get($movie, 'id');
        $title = Arr::get($movie, 'title');

        if (! $id || ! $title) {
            return null;
        }

        return [
            'id' => (string) $id,
            'source' => 'tmdb',
            'titulo' => $title,
            'sinopsis' => Arr::get($movie, 'overview'),
            'poster_url' => $this->makePosterUrl(Arr::get($movie, 'poster_path')),
            'release_date' => Arr::get($movie, 'release_date'),
        ];
    }

    private function extractYoutubeTrailer(array $movie): ?string
    {
        $videos = Arr::get($movie, 'videos.results', []);

        foreach ($videos as $video) {
            if (Arr::get($video, 'site') === 'YouTube' && Arr::get($video, 'type') === 'Trailer' && filled(Arr::get($video, 'key'))) {
                return 'https://www.youtube.com/watch?v='.Arr::get($video, 'key');
            }
        }

        return null;
    }

    private function makePosterUrl(?string $posterPath): ?string
    {
        if (! $posterPath) {
            return null;
        }

        return rtrim((string) config('services.tmdb.image_base_url'), '/').'/'.ltrim($posterPath, '/');
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.tmdb.base_url'), '/').'/'.ltrim($path, '/');
    }

    private function baseParams(): array
    {
        return [
            'api_key' => (string) config('services.tmdb.api_key'),
            'language' => (string) config('services.tmdb.language', 'es-ES'),
        ];
    }
}