<?php

namespace Database\Seeders;

use App\Models\Cine;
use Illuminate\Database\Seeder;

class CineSeeder extends Seeder
{
    public function run(): void
    {
        $cines = [
            ['nombre' => 'CineFlow Barceloneta', 'direccion_completa' => 'Carrer de la Marina, 16', 'ciudad' => 'Barcelona', 'provincia' => 'Barcelona'],
            ['nombre' => 'CineFlow Gràcia',      'direccion_completa' => 'Carrer de Verdi, 32',    'ciudad' => 'Barcelona', 'provincia' => 'Barcelona'],
            ['nombre' => 'CineFlow Madrid',       'direccion_completa' => 'Calle Gran Vía, 45',     'ciudad' => 'Madrid',    'provincia' => 'Madrid'],
        ];

        foreach ($cines as $data) {
            Cine::firstOrCreate(['nombre' => $data['nombre']], $data);
        }
    }
}
