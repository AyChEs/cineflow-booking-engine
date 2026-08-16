<?php

namespace Database\Seeders;

use App\Models\Cine;
use App\Models\Sala;
use Illuminate\Database\Seeder;

class SalaSeeder extends Seeder
{
    public function run(): void
    {
        $salas = [
            ['nombre' => 'Sala 1', 'capacidad' => 80,  'disposicion_butacas' => '8x10',  'cine' => 'CineFlow Barceloneta'],
            ['nombre' => 'Sala 2', 'capacidad' => 60,  'disposicion_butacas' => '6x10',  'cine' => 'CineFlow Barceloneta'],
            ['nombre' => 'Sala 1', 'capacidad' => 100, 'disposicion_butacas' => '10x10', 'cine' => 'CineFlow Gràcia'],
            ['nombre' => 'Sala 1', 'capacidad' => 120, 'disposicion_butacas' => '10x12', 'cine' => 'CineFlow Madrid'],
            ['nombre' => 'Sala 2', 'capacidad' => 80,  'disposicion_butacas' => '8x10',  'cine' => 'CineFlow Madrid'],
        ];

        foreach ($salas as $data) {
            $cine = Cine::where('nombre', $data['cine'])->first();
            if (!$cine) continue;
            Sala::firstOrCreate(
                ['nombre' => $data['nombre'], 'fk_cine_id' => $cine->id],
                ['capacidad' => $data['capacidad'], 'disposicion_butacas' => $data['disposicion_butacas'], 'fk_cine_id' => $cine->id]
            );
        }
    }
}
