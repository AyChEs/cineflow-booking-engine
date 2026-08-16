<?php

namespace App\Http\Controllers;

use App\Models\Cine;
use App\Models\Pelicula;
use App\Services\DevsApiHubMovieService;

class HomeController extends Controller
{
    public function index(DevsApiHubMovieService $api)
    {
        $peliculas = Pelicula::with('categorias')->latest()->take(8)->get();
        $cines     = Cine::withCount('salas')->get();

        $destacada = $peliculas->first();
        $tmdbHero  = null;

        try {
            $nowPlay = $api->getNowPlaying();

            if (! empty($nowPlay)) {
                $candidato  = $nowPlay[0];
                $localMatch = Pelicula::with('categorias')
                    ->whereRaw('LOWER(titulo) LIKE ?', ['%' . strtolower($candidato['title']) . '%'])
                    ->first();

                if ($localMatch) {
                    $destacada = $localMatch;
                } else {
                    $tmdbHero = $candidato;
                }
            }
        } catch (\Throwable) {
            // TMDB no disponible — seguimos con datos locales
        }

        return view('home', compact('peliculas', 'cines', 'destacada', 'tmdbHero'));
    }
}
