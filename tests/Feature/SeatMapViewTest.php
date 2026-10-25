<?php

namespace Tests\Feature;

use App\Models\Cine;
use App\Models\Pelicula;
use App\Models\Reserva;
use App\Models\ReservaSeat;
use App\Models\Sala;
use App\Models\SeatLock;
use App\Models\Sesion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeatMapViewTest extends TestCase
{
    use RefreshDatabase;

    private function sesion(int $capacidad, string $disposicion): Sesion
    {
        $sala = Sala::factory()->create([
            'fk_cine_id'          => Cine::factory()->create()->id,
            'capacidad'           => $capacidad,
            'disposicion_butacas' => $disposicion,
        ]);

        return Sesion::factory()->create([
            'fk_pelicula_id' => Pelicula::factory()->create()->id,
            'fk_sala_id'     => $sala->id,
            'fecha_hora'     => now()->addDay(),
            'preu_base'      => 9,
        ]);
    }

    private function abrirPaso2(Sesion $sesion, int $entradas = 2)
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('compra.step1.store'), ['sesion_id' => $sesion->id, 'entrades' => ['adult' => $entradas]]);

        return $this->get(route('compra.step2'))->assertOk();
    }

    public function test_el_paso_2_es_una_rejilla_con_una_celda_por_butaca(): void
    {
        $html = $this->abrirPaso2($this->sesion(50, 'standard'))->getContent();

        $this->assertStringContainsString('role="grid"', $html);
        $this->assertSame(50, substr_count($html, 'role="gridcell"'));
        $this->assertSame(1, substr_count($html, 'data-required="2"'));
    }

    public function test_una_sala_8x10_tiene_8_filas_de_10(): void
    {
        $html = $this->abrirPaso2($this->sesion(80, '8x10'))->getContent();

        $this->assertSame(80, substr_count($html, 'role="gridcell"'));
        $this->assertSame(8, substr_count($html, 'role="row"'));
        $this->assertStringContainsString('data-seat="H10"', $html);
        $this->assertStringNotContainsString('data-seat="A11"', $html);
    }

    public function test_las_butacas_vendidas_y_retenidas_lo_dicen_y_no_se_pueden_pulsar(): void
    {
        $sesion = $this->sesion(50, 'standard');
        $otra = Reserva::create(['fk_usuario_id' => null, 'fk_sesion_id' => $sesion->id, 'estat' => 'pagat', 'total_pagat' => 9]);
        ReservaSeat::create(['reserva_id' => $otra->id, 'sesion_id' => $sesion->id, 'butaca' => 'B3']);
        SeatLock::create(['sesion_id' => $sesion->id, 'butaca' => 'C4', 'user_id' => User::factory()->create()->id, 'session_token' => 'otra-persona', 'expires_at' => now()->addMinutes(5)]);

        $html = $this->abrirPaso2($sesion)->getContent();

        $this->assertMatchesRegularExpression('/data-seat="B3"[^>]*aria-disabled="true"[^>]*aria-label="Fila B, butaca 3, vendida"/', $html);
        $this->assertMatchesRegularExpression('/data-seat="C4"[^>]*aria-disabled="true"[^>]*aria-label="Fila C, butaca 4, retenida por otra persona"/', $html);
        $this->assertMatchesRegularExpression('/data-seat="A1"[^>]*aria-pressed="false"[^>]*aria-label="Fila A, butaca 1, libre"/', $html);
    }

    public function test_las_butacas_propias_salen_pulsadas_con_la_cuenta_atras(): void
    {
        $sesion = $this->sesion(50, 'standard');
        $this->abrirPaso2($sesion);
        $this->postJson(route('seat.lock'), ['sesion_id' => $sesion->id, 'butaca' => 'D5'])->assertJson(['ok' => true]);

        $html = $this->get(route('compra.step2'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-seat="D5"[^>]*aria-pressed="true"[^>]*aria-label="Fila D, butaca 5, seleccionada por ti"/', $html);
        $this->assertMatchesRegularExpression('/id="cuenta-atras"[^>]*data-expires="\d{4}-\d{2}-\d{2}T/', $html);
    }
}
