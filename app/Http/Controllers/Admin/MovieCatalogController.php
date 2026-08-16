<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TmdbMovieService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovieCatalogController extends Controller
{
    public function __construct(private readonly TmdbMovieService $tmdbMovieService)
    {
    }

    public function search(Request $request): JsonResponse
    {
        if (! $this->tmdbMovieService->isEnabled()) {
            return response()->json([
                'enabled' => false,
                'message' => 'TMDB is not configured yet. Add TMDB_ENABLED=true and TMDB_API_KEY to your .env file.',
                'data' => [],
            ]);
        }

        $request->validate([
            'query' => 'required|string|min:2|max:120',
        ]);

        return response()->json([
            'enabled' => true,
            'data' => $this->tmdbMovieService->searchMovies($request->string('query')->toString()),
        ]);
    }

    public function show(string $sourceId): JsonResponse
    {
        if (! $this->tmdbMovieService->isEnabled()) {
            return response()->json([
                'enabled' => false,
                'message' => 'TMDB is not configured yet. Add TMDB_ENABLED=true and TMDB_API_KEY to your .env file.',
            ], 503);
        }

        $movie = $this->tmdbMovieService->getMovieDetails($sourceId);

        if (! $movie) {
            return response()->json([
                'enabled' => true,
                'message' => 'Movie not found in TMDB.',
            ], 404);
        }

        return response()->json([
            'enabled' => true,
            'data' => $movie,
        ]);
    }
}