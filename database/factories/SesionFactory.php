<?php

namespace Database\Factories;

use App\Models\Sesion;
use App\Models\Pelicula;
use App\Models\Sala;
use Illuminate\Database\Eloquent\Factories\Factory;

class SesionFactory extends Factory
{
    protected $model = Sesion::class;

    public function definition(): array
    {
        return [
            'fk_pelicula_id' => Pelicula::factory(),
            'fk_sala_id'     => Sala::factory(),
            'fecha_hora'     => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'preu_base'      => $this->faker->randomFloat(2, 8.00, 20.00),
            'created_at'     => now(),
            'updated_at'     => now(),
        ];
    }
}
