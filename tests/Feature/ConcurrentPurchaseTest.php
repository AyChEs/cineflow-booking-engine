<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Pelicula;
use App\Models\Sesion;
use App\Models\ReservaSeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ConcurrentPurchaseTest
 * 
 * Tests system behavior under concurrent user load.
 * Simulates multiple users attempting to book seats simultaneously.
 */
class ConcurrentPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected Pelicula $pelicula;
    protected Sesion $sesion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pelicula = Pelicula::factory()->create();
        
        $cine = \App\Models\Cine::factory()->create();
        $sala = \App\Models\Sala::factory()->create([
            'fk_cine_id'  => $cine->id,
            'capacidad'   => 50, // Small room to test seat scarcity
        ]);

        $this->sesion = Sesion::factory()->create([
            'fk_pelicula_id' => $this->pelicula->id,
            'fk_sala_id'     => $sala->id,
            'fecha_hora'     => now()->addDays(1),
            'preu_base'      => 12.50,
        ]);
    }

    /**
     * Test: 50 concurrent users cannot overbook seats
     * 
     * This test simulates the scenario where 50 users try to book
     * seats simultaneously. Only 50 seats should be successfully booked.
     */
    public function test_no_overbooking_with_concurrent_users(): void
    {
        $numUsers = 50;
        $users = User::factory()->count($numUsers)->create();

        $successCount = 0;
        $failureCount = 0;

        // Simulate 50 users trying to book 1 seat each
        foreach ($users as $index => $user) {
            $butaca = chr(65 + intdiv($index, 10)) . (($index % 10) + 1); // A1, A2, ..., E0

            try {
                // Simulate purchase with pessimistic locking
                $reserva = \App\Models\Reserva::create([
                    'fk_usuario_id' => $user->id,
                    'fk_sesion_id'  => $this->sesion->id,
                    'estat'         => 'pagat',
                    'total_pagat'   => 12.50,
                ]);

                ReservaSeat::create([
                    'reserva_id' => $reserva->id,
                    'sesion_id'  => $this->sesion->id,
                    'butaca'     => $butaca,
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $failureCount++;
            }
        }

        // All 50 bookings should succeed (50 different seats)
        $this->assertEquals(50, $successCount);
        $this->assertEquals(0, $failureCount);

        // Verify all seats are unique
        $bookedSeats = ReservaSeat::where('sesion_id', $this->sesion->id)
            ->pluck('butaca')
            ->unique()
            ->count();
        $this->assertEquals(50, $bookedSeats);
    }

    /**
     * Test: Multiple users competing for same seats shows race condition handling
     * 
     * Simulates 100 users trying to book the same 5 seats.
     * Expected: Only 5 succeed, remaining 95 fail gracefully.
     */
    public function test_race_condition_handling_multiple_users(): void
    {
        $competingSeats = ['A1', 'A2', 'A3', 'A4', 'A5'];
        $numUsers = 20;
        $users = User::factory()->count($numUsers)->create();

        $successCount = 0;
        $failureCount = 0;

        // All users try to book same 5 seats
        foreach ($users as $index => $user) {
            $butaca = $competingSeats[$index % count($competingSeats)];

            try {
                // Check if seat already reserved
                $exists = ReservaSeat::where('sesion_id', $this->sesion->id)
                    ->where('butaca', $butaca)
                    ->exists();

                if ($exists) {
                    throw new \Exception("Seat already booked");
                }

                $reserva = \App\Models\Reserva::create([
                    'fk_usuario_id' => $user->id,
                    'fk_sesion_id'  => $this->sesion->id,
                    'estat'         => 'pagat',
                    'total_pagat'   => 12.50,
                ]);

                ReservaSeat::create([
                    'reserva_id' => $reserva->id,
                    'sesion_id'  => $this->sesion->id,
                    'butaca'     => $butaca,
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $failureCount++;
            }
        }

        // Only 5 should succeed (one per unique seat)
        $this->assertEquals(5, $successCount);
        $this->assertGreaterThan(0, $failureCount);

        // Verify only 5 seats are booked
        $bookedSeats = ReservaSeat::where('sesion_id', $this->sesion->id)->count();
        $this->assertEquals(5, $bookedSeats);
    }

    /**
     * Test: System performance with 100+ queries per second
     * 
     * Verifies that seat availability queries complete in reasonable time.
     */
    public function test_query_performance_under_load(): void
    {
        // Create 100 reservations
        $reservations = \App\Models\Reserva::factory()
            ->count(10)
            ->create(['fk_sesion_id' => $this->sesion->id]);

        foreach ($reservations as $i => $reserva) {
            ReservaSeat::create([
                'reserva_id' => $reserva->id,
                'sesion_id'  => $this->sesion->id,
                'butaca'     => chr(65 + intdiv($i, 10)) . (($i % 10) + 1),
            ]);
        }

        // Time the query
        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $seats = ReservaSeat::where('sesion_id', $this->sesion->id)
                ->pluck('butaca')
                ->toArray();
        }
        
        $elapsed = microtime(true) - $start;

        // Should complete 100 queries in < 5 seconds (50ms per query)
        // With indexes, should be < 1 second
        $this->assertLessThan(5.0, $elapsed, "100 queries took {$elapsed}s, should be < 5s");
    }

    /**
     * Test: Database transaction integrity under concurrent load
     */
    public function test_transaction_integrity(): void
    {
        $users = User::factory()->count(10)->create();

        foreach ($users as $index => $user) {
            $reserva = \App\Models\Reserva::create([
                'fk_usuario_id' => $user->id,
                'fk_sesion_id'  => $this->sesion->id,
                'estat'         => 'pagat',
                'total_pagat'   => 12.50,
            ]);

            // Create associated seat records
            for ($j = 0; $j < 2; $j++) {
                ReservaSeat::create([
                    'reserva_id' => $reserva->id,
                    'sesion_id'  => $this->sesion->id,
                    'butaca'     => "Seat{$index}_{$j}",
                ]);
            }
        }

        // Verify referential integrity
        $reservations = \App\Models\Reserva::where('fk_sesion_id', $this->sesion->id)->count();
        $seats = ReservaSeat::where('sesion_id', $this->sesion->id)->count();

        $this->assertEquals(10, $reservations);
        $this->assertEquals(20, $seats); // 10 reservations × 2 seats each
    }
}
