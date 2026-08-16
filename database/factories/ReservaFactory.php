<?php

namespace Database\Factories;

use App\Models\Reserva;
use App\Models\User;
use App\Models\Sesion;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservaFactory extends Factory
{
    protected $model = Reserva::class;

    public function definition(): array
    {
        return [
            'fk_usuario_id' => User::factory(),
            'fk_sesion_id'  => Sesion::factory(),
            'tipus_entrada' => $this->faker->randomElement(['adult', 'infantil', 'jubilat', 'discapacitat']),
            'total_pagat'   => $this->faker->randomFloat(2, 12.50, 75.00),
            'estat'         => $this->faker->randomElement(['pendent', 'pagat', 'cancelat']),
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
    }
}
