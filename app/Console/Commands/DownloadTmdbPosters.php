<?php

namespace App\Console\Commands;

use App\Models\Pelicula;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadTmdbPosters extends Command
{
    protected $signature = 'posters:download {--force : Redownload all posters even if local exists}';
    protected $description = 'Descargar posters de TMDB y guardarlos localmente';

    public function handle()
    {
        $force = $this->option('force');
        $moviesPath = 'public/posters/movies';
        
        // Crear directorio si no existe
        Storage::makeDirectory($moviesPath);

        $peliculas = Pelicula::whereNotNull('poster_path')
            ->where('poster_path', 'like', 'https://image.tmdb.org%')
            ->get();

        if ($peliculas->isEmpty()) {
            $this->info('✓ No hay películas con posters de TMDB');
            return Command::SUCCESS;
        }

        $this->info("Descargando {$peliculas->count()} posters...\n");

        foreach ($peliculas as $pelicula) {
            try {
                // Extraer el nombre de archivo de la URL
                $fileName = basename(parse_url($pelicula->poster_path, PHP_URL_PATH));
                $localPath = "{$moviesPath}/{$fileName}";

                // Verificar si ya existe localmente
                if (Storage::exists($localPath) && !$force) {
                    $this->line("- Saltando {$pelicula->titulo} (ya descargado)");
                    continue;
                }

                // Descargar desde TMDB
                $posterContent = @file_get_contents($pelicula->poster_path, false, stream_context_create([
                    'http' => [
                        'timeout' => 10,
                        'user_agent' => 'CineFlow Cinema/1.0 (+https://cineflow.app)',
                    ],
                ]));

                if ($posterContent === false) {
                    $this->error("✗ ERROR descargando {$pelicula->titulo}");
                    continue;
                }

                // Guardar localmente
                Storage::put($localPath, $posterContent);

                // Actualizar en BD con ruta local
                $publicUrl = '/storage/posters/movies/' . $fileName;
                $pelicula->update(['poster_path' => $publicUrl]);

                $this->line("✓ {$pelicula->titulo}");
            } catch (\Throwable $e) {
                $this->error("✗ {$pelicula->titulo}: {$e->getMessage()}");
            }
        }

        $this->info("\nDescarga completada.");
        return Command::SUCCESS;
    }
}
