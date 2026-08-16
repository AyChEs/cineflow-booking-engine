<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Extra customer accounts so the admin panel and booking history have
 * a realistic amount of data, not just the three canonical demo logins.
 */
class ClientSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $names = [
            ['Laura', 'Gomez Ibanez'], ['Marc', 'Puig Serra'], ['Aisha', 'Benali'],
            ['Thomas', 'Dubois'], ['Elena', 'Petrova'], ['Diego', 'Fernandez Ortiz'],
            ['Noor', 'Haddad'], ['Kenji', 'Tanaka'], ['Sofia', 'Martins'],
            ['Youssef', 'Amrani'], ['Clara', 'Serrano'], ['Adam', 'Nilsson'],
            ['Valentina', 'Rossi'], ['Bilal', 'Idrissi'], ['Ingrid', 'Weber'],
            ['Pablo', 'Torres Vidal'], ['Chloe', 'Lambert'], ['Samir', 'Chraibi'],
            ['Mireille', 'Costa'], ['Erik', 'Klein'],
        ];

        $password = Hash::make('cliente1234');

        foreach ($names as $i => [$first, $last]) {
            User::firstOrCreate(
                ['email' => strtolower($first).'.'.strtolower(str_replace(' ', '', $last)).'@example.com'],
                [
                    'name'              => $first,
                    'apellidos'         => $last,
                    'password'          => $password,
                    'rol'               => 'cliente',
                    'telefono'          => sprintf('6%08d', 10000000 + $i * 37),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
