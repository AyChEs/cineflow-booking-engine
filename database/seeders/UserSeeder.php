<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = [
            [
                'name'              => 'Admin',
                'apellidos'         => 'CineFlow',
                'email'             => 'admin@cineflow.test',
                'password'          => Hash::make('admin1234'),
                'rol'               => 'admin',
                'telefono'          => '600000001',
                'email_verified_at' => now(),
            ],
            [
                'name'              => 'Taquilla',
                'apellidos'         => 'CineFlow',
                'email'             => 'taquilla@cineflow.test',
                'password'          => Hash::make('taquilla1234'),
                'rol'               => 'taquilla',
                'telefono'          => '600000002',
                'email_verified_at' => now(),
            ],
            [
                'name'              => 'Cliente',
                'apellidos'         => 'Demo',
                'email'             => 'cliente@cineflow.test',
                'password'          => Hash::make('cliente1234'),
                'rol'               => 'cliente',
                'telefono'          => '600000003',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(['email' => $data['email']], $data);
        }
    }
}
