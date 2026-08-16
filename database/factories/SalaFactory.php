<?php

namespace Database\Factories;

use App\Models\Sala;
use App\Models\Cine;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalaFactory extends Factory
{
    protected $model = Sala::class;

    public function definition(): array
    {
        return [
            'fk_cine_id'           => Cine::factory(),
            'nombre'               => 'Sala '.$this->faker->unique()->numberBetween(1, 9999),
            'capacidad'            => $this->faker->numberBetween(50, 200),
            'disposicion_butacas'  => $this->faker->randomElement(['standard', 'vip', 'premium']),
            'created_at'           => now(),
            'updated_at'           => now(),
        ];
    }
}
