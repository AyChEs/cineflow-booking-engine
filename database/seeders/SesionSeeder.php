<?php

namespace Database\Seeders;

use App\Models\Pelicula;
use App\Models\Sala;
use App\Models\Sesion;
use Illuminate\Database\Seeder;

class SesionSeeder extends Seeder
{
    public function run(): void
    {
        $peliculas = Pelicula::all();
        $salas = Sala::all();

        if ($peliculas->isEmpty() || $salas->isEmpty()) {
            return;
        }

        $horarios = ['10:00', '16:00', '21:30'];
        $base = now()->startOfDay();

        // Includes the past 10 days too, so there's a real booking history to
        // seed reservations against (ReservaSeeder), not just upcoming showtimes.
        foreach (range(-10, 13) as $dia) {
            $fecha = $base->copy()->addDays($dia);
            foreach ($salas as $sala) {
                $pelicula = $peliculas->random();
                foreach ($horarios as $hora) {
                    [$h, $m] = explode(':', $hora);
                    Sesion::firstOrCreate([
                        'fk_sala_id'     => $sala->id,
                        'fk_pelicula_id' => $pelicula->id,
                        'fecha_hora'     => $fecha->copy()->setHour((int)$h)->setMinute((int)$m),
                    ], [
                        'preu_base' => 8.50,
                    ]);
                }
            }
        }
    }
}
