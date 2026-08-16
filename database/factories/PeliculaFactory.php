<?php

namespace Database\Factories;

use App\Models\Pelicula;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeliculaFactory extends Factory
{
    protected $model = Pelicula::class;

    public function definition(): array
    {
        return [
            'titulo'             => $this->faker->sentence(3),
            'sinopsis'           => $this->faker->paragraph(),
            'duracion_min'       => $this->faker->numberBetween(90, 180),
            'classificacio_edad' => $this->faker->randomElement(['TP', '+7', '+12', '+16', '+18']),
            'trailer_url'        => null,
            'poster_path'        => null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ];
    }
}
