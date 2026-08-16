<?php

namespace Tests\Feature;

use App\Models\Cine;
use App\Models\Pelicula;
use App\Models\Reserva;
use App\Models\ReservaSeat;
use App\Models\Sala;
use App\Models\Sesion;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\SeatAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceLayerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Sesion $sesion;
    protected PurchaseService $purchaseService;
    protected SeatAvailabilityService $seatService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $pelicula   = Pelicula::factory()->create();
        $cine       = Cine::factory()->create();
        $sala       = Sala::factory()->create(['fk_cine_id' => $cine->id, 'capacidad' => 100]);

        $this->sesion = Sesion::factory()->create([
            'fk_pelicula_id' => $pelicula->id,
            'fk_sala_id'     => $sala->id,
            'fecha_hora'     => now()->addDays(1),
            'preu_base'      => 12.50,
        ]);

        $this->purchaseService = app(PurchaseService::class);
        $this->seatService     = app(SeatAvailabilityService::class);
    }

    public function test_confirm_purchase_creates_reserva_and_seats(): void
    {
        $butacas = ['A1', 'A2'];

        $reserva = $this->purchaseService->confirmPurchase(
            $this->user->id,
            $this->sesion->id,
            $butacas,
            'adult',
            25.00
        );

        $this->assertEquals('pagat', $reserva->estat);
        $this->assertEquals(2, ReservaSeat::where('reserva_id', $reserva->id)->count());
    }

    public function test_seat_availability_detects_reserved(): void
    {
        $reserva = Reserva::create([
            'fk_usuario_id' => $this->user->id,
            'fk_sesion_id'  => $this->sesion->id,
            'tipus_entrada' => 'adult',
            'total_pagat'   => 12.50,
            'estat'         => 'pagat',
        ]);

        ReservaSeat::create([
            'reserva_id' => $reserva->id,
            'sesion_id'  => $this->sesion->id,
            'butaca'     => 'A1',
        ]);

        $this->assertTrue($this->seatService->isSeatReserved($this->sesion->id, 'A1'));
        $this->assertFalse($this->seatService->isSeatReserved($this->sesion->id, 'A2'));
        $this->assertFalse($this->seatService->isSeatAvailable($this->sesion->id, 'A1'));
        $this->assertTrue($this->seatService->isSeatAvailable($this->sesion->id, 'A2'));
    }

    public function test_get_reserved_seats_returns_all_seats_for_session(): void
    {
        $reserva = Reserva::create([
            'fk_usuario_id' => $this->user->id,
            'fk_sesion_id'  => $this->sesion->id,
            'tipus_entrada' => 'adult',
            'total_pagat'   => 25.00,
            'estat'         => 'pagat',
        ]);

        foreach (['A1', 'A2', 'B5'] as $b) {
            ReservaSeat::create([
                'reserva_id' => $reserva->id,
                'sesion_id'  => $this->sesion->id,
                'butaca'     => $b,
            ]);
        }

        $reserved = $this->seatService->getReservedSeats($this->sesion->id);
        $this->assertCount(3, $reserved);
        $this->assertContains('A1', $reserved);
        $this->assertContains('B5', $reserved);
    }
}
