<?php

namespace Tests\Feature;

use App\Models\Cine;
use App\Models\Pelicula;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\Sesion;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Regresiones de los arreglos de la sesión 00b: entrada por rol, enlace de
 * recuperación solo en modo demo y Bizum en el pago simulado.
 */
class BaseFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_que_entra_acaba_en_su_panel(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->get('/dashboard')->assertRedirect(route('cliente.dashboard'));
    }

    public function test_admin_ve_el_panel_general(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_sin_modo_demo_el_enlace_se_envia_por_correo_y_no_se_muestra(): void
    {
        config(['auth.demo_reset_link' => false]);
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionMissing('reset_link')
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_bizum_exige_un_movil_valido(): void
    {
        $user = User::factory()->create();
        $cine = Cine::factory()->create();
        $sala = Sala::factory()->create(['fk_cine_id' => $cine->id, 'capacidad' => 50, 'disposicion_butacas' => 'standard']);
        $sesion = Sesion::factory()->create([
            'fk_pelicula_id' => Pelicula::factory()->create()->id,
            'fk_sala_id'     => $sala->id,
            'fecha_hora'     => now()->addDay(),
            'preu_base'      => 10,
        ]);

        $this->actingAs($user);
        $this->post(route('compra.step1.store'), ['sesion_id' => $sesion->id, 'entrades' => ['adult' => 1]]);
        $this->post(route('compra.step2.store'), ['butaques' => 'A1']);

        $this->post(route('compra.step3.store'), [
            'nom'           => 'Prueba',
            'email'         => 'prueba@example.com',
            'metode'        => 'bizum',
            'telefon_bizum' => '12345',
        ])->assertSessionHasErrors('telefon_bizum');

        $this->assertSame(0, Reserva::count());
    }
}
