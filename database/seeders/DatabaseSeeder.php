<?php

namespace Database\Seeders;

use App\Services\DevsApiHubMovieService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Base sin peliculas/sesiones (usuarios, cines, salas, categorias)
        $this->call([
            UserSeeder::class,
            ClientSeeder::class,
            CategoriaSeeder::class,
            CineSeeder::class,
            SalaSeeder::class,
        ]);

        // 2) Peliculas + sesiones: preferimos TMDB (taquilla ES en vivo).
        //    Si no hay API o falla, fallback seeder demo hardcoded.
        $api = app(DevsApiHubMovieService::class);
        $usedTmdb = false;

        if ($api->isEnabled()) {
            $this->command->info('TMDB activo → sincronizando taquilla española...');
            try {
                Artisan::call('sesions:generate-from-releases', [], $this->command->getOutput());
                $usedTmdb = true;
            } catch (\Throwable $e) {
                $this->command->warn('TMDB falló: '.$e->getMessage().' → fallback seeder demo.');
            }
        }

        if (! $usedTmdb) {
            $this->command->info('TMDB no disponible → usando seeder demo.');
            $this->call([
                PeliculaSeeder::class,
                SesionSeeder::class,
            ]);
        }

        // 3) Historial de reservas realista sobre las sesiones ya sembradas.
        $this->call([
            ReservaSeeder::class,
        ]);
    }
}
