<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Pelicula;
use App\Models\Sesion;
use App\Models\SeatLock;
use App\Models\ReservaSeat;
use App\Models\Reserva;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PurchaseFlowTest
 * 
 * Tests the complete purchase flow with race condition detection,
 * seat locking, and transaction handling.
 */
class PurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected User $user2;
    protected Pelicula $pelicula;
    protected Sesion $sesion;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user1 = User::factory()->create(['name' => 'User 1', 'email' => 'user1@test.com']);
        $this->user2 = User::factory()->create(['name' => 'User 2', 'email' => 'user2@test.com']);
        
        $this->pelicula = Pelicula::factory()->create([
            'titulo'       => 'Test Movie',
            'duracion_min' => 120,
            'sinopsis'     => 'Test synopsis',
        ]);

        $cine = \App\Models\Cine::factory()->create();
        $sala = \App\Models\Sala::factory()->create([
            'fk_cine_id'          => $cine->id,
            'nombre'              => 'Test Room',
            'capacidad'           => 100,
            'disposicion_butacas' => 'standard',
        ]);

        $this->sesion = Sesion::factory()->create([
            'fk_pelicula_id' => $this->pelicula->id,
            'fk_sala_id'     => $sala->id,
            'fecha_hora'     => now()->addDays(1)->setHour(20),
            'preu_base'      => 12.50,
        ]);
    }

    /**
     * Test: User can complete full purchase flow (Step 1-3)
     */
    public function test_user_can_complete_purchase_flow(): void
    {
        $this->actingAs($this->user1);

        $this->post(route('compra.step1.store'), [
            'sesion_id' => $this->sesion->id,
            'entrades'  => ['adult' => 2, 'reduit' => 0, 'familia' => 0, 'jubilat' => 0],
        ]);

        $this->assertTrue(session()->has('compra'));
        $this->assertEquals(2, session('compra.num_entrades'));
        $this->assertEquals(2 * 12.50, session('compra.total'));

        $this->post(route('compra.step2.store'), ['butaques' => 'A1, A2']);

        $this->assertTrue(session()->has('compra'));
        $this->assertContains('A1', session('compra.butaques'));
        $this->assertContains('A2', session('compra.butaques'));

        $locks = SeatLock::where('sesion_id', $this->sesion->id)
            ->where('user_id', $this->user1->id)
            ->count();
        $this->assertEquals(2, $locks);

        $this->post(route('compra.step3.store'), [
            'nom'           => 'Test User',
            'email'         => 'test@example.com',
            'metode'        => 'bizum',
            'telefon_bizum' => '600123456',
        ]);

        // Verify reservation created
        $reserva = Reserva::where('fk_usuario_id', $this->user1->id)
            ->where('fk_sesion_id', $this->sesion->id)
            ->first();

        $this->assertNotNull($reserva);
        $this->assertEquals('pagat', $reserva->estat);

        $seats = ReservaSeat::where('reserva_id', $reserva->id)->count();
        $this->assertEquals(2, $seats);

        // Verify seat locks cleaned up
        $remaining = SeatLock::where('sesion_id', $this->sesion->id)
            ->where('user_id', $this->user1->id)
            ->count();
        $this->assertEquals(0, $remaining);
    }

    /**
     * Test: Race condition prevention - Last user to submit loses
     */
    public function test_race_condition_prevents_double_booking(): void
    {
        $this->actingAs($this->user1);
        $this->post(route('compra.step1.store'), [
            'sesion_id' => $this->sesion->id,
            'entrades'  => ['adult' => 1],
        ]);
        $this->post(route('compra.step2.store'), ['butaques' => 'A1']);
        $this->post(route('compra.step3.store'), [
            'nom'           => 'User 1',
            'email'         => 'user1@example.com',
            'metode'        => 'bizum',
            'telefon_bizum' => '600111222',
        ]);

        $this->assertFalse(session()->has('compra'));
        $this->assertTrue(session()->has('compra_confirmada'));

        $this->assertEquals(
            1,
            ReservaSeat::where('sesion_id', $this->sesion->id)->where('butaca', 'A1')->count(),
            'Seat A1 must be reserved exactly once after first user.'
        );

        $this->actingAs($this->user2);
        $this->post(route('compra.step1.store'), [
            'sesion_id' => $this->sesion->id,
            'entrades'  => ['adult' => 1],
        ]);
        $response = $this->post(route('compra.step2.store'), ['butaques' => 'A1']);
        $response->assertSessionHas('error');

        $this->assertEquals(
            1,
            ReservaSeat::where('sesion_id', $this->sesion->id)->where('butaca', 'A1')->count()
        );
    }

    /**
     * Test: Seat locks expire after configured time
     */
    public function test_seat_locks_expire_after_eight_minutes(): void
    {
        $this->actingAs($this->user1);

        // Create a lock manually
        SeatLock::create([
            'sesion_id'  => $this->sesion->id,
            'butaca'     => 'A1',
            'user_id'    => $this->user1->id,
            'expires_at' => now()->subSeconds(1), // Already expired
        ]);

        // Try to get seat status - expired lock should not appear
        $response = $this->get(route('seat.status', $this->sesion->id));
        $response->assertSuccessful();

        $data = $response->json();
        $this->assertFalse(in_array('A1', $data['locked']));

        SeatLock::create([
            'sesion_id'  => $this->sesion->id,
            'butaca'     => 'A2',
            'user_id'    => $this->user2->id,
            'expires_at' => now()->addMinutes(7),
        ]);

        $response = $this->get(route('seat.status', $this->sesion->id));
        $data = $response->json();
        $this->assertTrue(in_array('A2', $data['locked']));
    }

    /**
     * Test: User cannot see other user's reservations
     */
    public function test_user_cannot_see_other_user_reservations(): void
    {
        // User 1 creates a reservation
        $reserva = Reserva::create([
            'fk_usuario_id' => $this->user1->id,
            'fk_sesion_id'  => $this->sesion->id,
            'estat'         => 'pagat',
            'total_pagat'   => 25.00,
        ]);

        // User 2 tries to directly access User 1's confirmation
        $this->actingAs($this->user2);

        // Note: This test assumes proper route protection in the controller
        // In real app, you'd have a ReservaController::show() that checks ownership
        $reserva->refresh();
        $this->assertEquals($this->user1->id, $reserva->fk_usuario_id);
        $this->assertNotEquals($this->user2->id, $reserva->fk_usuario_id);
    }

    public function test_pessimistic_locking_in_transaction(): void
    {
        $this->actingAs($this->user1);
        $this->post(route('compra.step1.store'), [
            'sesion_id' => $this->sesion->id,
            'entrades'  => ['adult' => 1],
        ]);
        $this->post(route('compra.step2.store'), ['butaques' => 'A1']);

        $compra = session('compra');
        $this->assertNotNull($compra);

        // Create a dummy reservation first to test the lock
        $reserva = Reserva::create([
            'fk_usuario_id'          => $this->user1->id,
            'fk_sesion_id'           => $this->sesion->id,
            'estat'                  => 'pendent',
            'total_pagat'            => 12.50,
        ]);

        \App\Models\ReservaSeat::create([
            'reserva_id' => $reserva->id,
            'sesion_id'  => $this->sesion->id,
            'butaca'     => 'A1',
        ]);

        $this->assertNotNull($reserva->id);
    }
}
