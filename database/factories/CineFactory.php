<?php

namespace Database\Factories;

use App\Models\Cine;
use Illuminate\Database\Eloquent\Factories\Factory;

class CineFactory extends Factory
{
    protected $model = Cine::class;

    public function definition(): array
    {
        return [
            'nombre'             => 'Cine '.$this->faker->unique()->numberBetween(1, 99999),
            'direccion_completa' => $this->faker->address(),
            'ciudad'             => $this->faker->city(),
            'provincia'          => $this->faker->state(),
            'created_at'         => now(),
            'updated_at'         => now(),
        ];
    }
}
