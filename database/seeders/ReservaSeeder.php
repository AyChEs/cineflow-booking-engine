<?php

namespace Database\Seeders;

use App\Models\Reserva;
use App\Models\ReservaSeat;
use App\Models\Sesion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Realistic purchase history: past sessions get paid bookings (so the admin
 * dashboard and "my bookings" pages aren't empty), upcoming sessions get a mix
 * of paid/pending/cancelled bookings. Caps the total at TARGET_TOTAL instead of
 * seeding every session, since the number of sessions varies a lot depending on
 * whether the demo fallback or the live TMDB/Yelmo sync generated the cartelera.
 */
class ReservaSeeder extends Seeder
{
    /** label => price factor, mirrors CompraController::ENTRADA_TYPES */
    private const FACTORS = [
        'adult'   => 1.00,
        'reduit'  => 0.80,
        'familia' => 0.82,
        'jubilat' => 0.70,
    ];

    /** Total reservations to create, regardless of how many sessions exist
     *  (the number of sessions varies a lot depending on whether the demo
     *  seeder or the live TMDB/Yelmo sync generated the cartelera). */
    private const TARGET_TOTAL = 220;

    public function run(): void
    {
        $clients = User::where('rol', 'cliente')->get();
        if ($clients->isEmpty()) {
            return;
        }

        $past = Sesion::where('fecha_hora', '<', now())->with('sala')->get();
        $upcoming = Sesion::where('fecha_hora', '>=', now())->with('sala')->get();

        // Split the fixed total across whichever buckets actually have
        // sessions, so the volume stays realistic no matter which cartelera
        // source (demo fallback vs. live TMDB/Yelmo sync) generated them.
        $buckets = array_filter([
            ['sessions' => $past, 'alwaysPaid' => true],
            ['sessions' => $upcoming, 'alwaysPaid' => false],
        ], fn ($b) => $b['sessions']->isNotEmpty());

        if (empty($buckets)) {
            return;
        }

        $perBucket = (int) ceil(self::TARGET_TOTAL / count($buckets));

        foreach ($buckets as $bucket) {
            $this->seedForSessions($bucket['sessions'], $clients, $bucket['alwaysPaid'], $perBucket);
        }
    }

    private function seedForSessions($sessions, $clients, bool $alwaysPaid, int $targetCount): void
    {
        // Sample sessions (with repetition allowed across different draws)
        // until we hit the target, instead of guaranteeing every single
        // session gets a booking — with hundreds of sessions that would
        // massively overshoot a realistic reservation count.
        $created = 0;
        $attempts = 0;
        $maxAttempts = $targetCount * 5;

        while ($created < $targetCount && $attempts < $maxAttempts) {
            $attempts++;
            $sesion = $sessions->random();

            $seatPool = $this->seatPool($sesion->sala->disposicion_butacas ?? '8x10');
            $taken = ReservaSeat::where('sesion_id', $sesion->id)->pluck('butaca')->all();
            $available = array_values(array_diff($seatPool, $taken));

            if (empty($available)) {
                continue;
            }

            $seatCount = min(random_int(1, 4), count($available));
            $seats = [];
            for ($s = 0; $s < $seatCount; $s++) {
                $idx = array_rand($available);
                $seats[] = $available[$idx];
                unset($available[$idx]);
                $available = array_values($available);
            }

            $tipo = array_rand(self::FACTORS);
            $total = round($sesion->preu_base * self::FACTORS[$tipo] * count($seats), 2);

            $estat = $alwaysPaid
                ? 'pagat'
                : (['pagat', 'pagat', 'pendent', 'cancelat'])[random_int(0, 3)];

            $reserva = Reserva::create([
                'fk_usuario_id'  => $clients->random()->id,
                'fk_sesion_id'   => $sesion->id,
                'tipus_entrada'  => $tipo,
                'total_pagat'    => $total,
                'estat'          => $estat,
                'ticket_token'   => $estat === 'pagat' ? Str::random(40) : null,
            ]);

            foreach ($seats as $seat) {
                ReservaSeat::create([
                    'reserva_id' => $reserva->id,
                    'sesion_id'  => $sesion->id,
                    'butaca'     => $seat,
                ]);
            }

            $created++;
        }
    }

    /** Parses "8x10" into seat labels A1..A10, B1..B10, ... */
    private function seatPool(string $disposicion): array
    {
        [$rows, $cols] = array_map('intval', explode('x', $disposicion));
        $seats = [];
        for ($r = 0; $r < $rows; $r++) {
            $letter = chr(65 + $r);
            for ($c = 1; $c <= $cols; $c++) {
                $seats[] = $letter.$c;
            }
        }

        return $seats;
    }
}
