<?php

namespace Tests\Feature;

use App\Models\Cine;
use App\Models\Pelicula;
use App\Models\Sala;
use App\Models\SeatLock;
use App\Models\Sesion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Una butaca bloqueada por un invitado (user_id nulo) también está retenida
 * para quien tiene sesión iniciada, y al revés.
 */
class BloqueoInvitadoTest extends TestCase
{
    use RefreshDatabase;

    private Sesion $sesion;

    protected function setUp(): void
    {
        parent::setUp();
        $sala = Sala::factory()->create(['fk_cine_id' => Cine::factory()->create()->id, 'capacidad' => 50, 'disposicion_butacas' => 'standard']);
        $this->sesion = Sesion::factory()->create([
            'fk_pelicula_id' => Pelicula::factory()->create()->id,
            'fk_sala_id'     => $sala->id,
            'fecha_hora'     => now()->addDay(),
        ]);
    }

    private function bloqueoDeInvitado(string $butaca): SeatLock
    {
        return SeatLock::create([
            'sesion_id'     => $this->sesion->id,
            'butaca'        => $butaca,
            'user_id'       => null,
            'session_token' => 'sesion-del-invitado',
            'expires_at'    => now()->addMinutes(8),
        ]);
    }

    public function test_un_usuario_no_puede_quitar_la_butaca_a_un_invitado(): void
    {
        $lock = $this->bloqueoDeInvitado('A1');

        $this->actingAs(User::factory()->create())
            ->postJson(route('seat.lock'), ['sesion_id' => $this->sesion->id, 'butaca' => 'A1'])
            ->assertJson(['ok' => false, 'reason' => 'locked']);

        $this->assertNull($lock->fresh()->user_id);
        $this->assertSame('sesion-del-invitado', $lock->fresh()->session_token);
    }

    public function test_el_estado_marca_retenida_la_butaca_del_invitado(): void
    {
        $this->bloqueoDeInvitado('A2');

        $this->actingAs(User::factory()->create())
            ->getJson(route('seat.status', $this->sesion->id))
            ->assertJsonPath('locked', ['A2'])
            ->assertJsonPath('mine', []);
    }

    public function test_el_paso_2_no_acepta_la_butaca_del_invitado(): void
    {
        $this->bloqueoDeInvitado('A3');
        $this->actingAs(User::factory()->create());
        $this->post(route('compra.step1.store'), ['sesion_id' => $this->sesion->id, 'entrades' => ['adult' => 1]]);

        $this->post(route('compra.step2.store'), ['butaques' => 'A3'])->assertSessionHas('error');
    }

    public function test_un_invitado_tampoco_puede_quitarsela_a_un_usuario(): void
    {
        SeatLock::create([
            'sesion_id'     => $this->sesion->id,
            'butaca'        => 'A4',
            'user_id'       => User::factory()->create()->id,
            'session_token' => 'sesion-del-usuario',
            'expires_at'    => now()->addMinutes(8),
        ]);

        $this->postJson(route('seat.lock'), ['sesion_id' => $this->sesion->id, 'butaca' => 'A4'])
            ->assertJson(['ok' => false, 'reason' => 'locked']);
    }
}
