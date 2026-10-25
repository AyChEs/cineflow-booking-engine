<?php

namespace App\Console\Commands;

use App\Models\Pelicula;
use App\Services\CartelSustituto;
use Illuminate\Console\Command;

/**
 * Carteles sustitutos para entornos sin acceso a TMDB (local, CI sin red).
 * Pinta un cartel tipográfico con la marca y su fondo panorámico en public/carteles.
 */
class GeneratePosterPlaceholders extends Command
{
    protected $signature = 'posters:generate-placeholders {--solo-sin-poster : Solo las películas sin póster}';

    protected $description = 'Generar carteles sustitutos tipográficos (WebP) para las películas';

    public function handle(CartelSustituto $carteles): int
    {
        $peliculas = Pelicula::with('categorias')
            ->when($this->option('solo-sin-poster'), fn ($q) => $q->whereNull('poster_path'))
            ->get();

        $this->info("Generando {$peliculas->count()} carteles...");

        foreach ($peliculas as $pelicula) {
            try {
                $pelicula->update(['poster_path' => $carteles->generar($pelicula)]);
                $this->line("  {$pelicula->titulo}");
            } catch (\Throwable $e) {
                $this->error("  {$pelicula->titulo}: {$e->getMessage()}");
            }
        }

        $this->info('Carteles generados en public/'.CartelSustituto::CARPETA.'.');

        return self::SUCCESS;
    }
}
