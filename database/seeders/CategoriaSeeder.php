<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            1  => 'Acción',
            2  => 'Aventura',
            3  => 'Ciencia Ficción',
            4  => 'Comedia',
            5  => 'Drama',
            6  => 'Terror',
            7  => 'Thriller',
            8  => 'Romance',
            9  => 'Animación',
            10 => 'Documental',
            11 => 'Fantasía',
            12 => 'Musical',
            13 => 'Western',
            14 => 'Histórica',
            15 => 'Bélica',
        ];

        foreach ($categorias as $id => $nombre) {
            DB::table('categorias')->insertOrIgnore([
                'id'         => $id,
                'nombre'     => $nombre,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
