<?php

namespace Tests\Feature;

use App\Models\Cine;
use App\Models\Pelicula;
use App\Models\Sala;
use App\Models\Sesion;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Guarda de dinero real: con claves «live» y sin STRIPE_PERMITIR_LIVE=true, Stripe queda desactivado
 * (pago simulado, aviso en el log) y nunca se llega a crear una sesión de Checkout.
 */
class StripeSoloPruebasTest extends TestCase
{
    use RefreshDatabase;

    private function claves(?string $key, ?string $secret, mixed $permitir = null): void
    {
        config([
            'services.stripe.key'            => $key,
            'services.stripe.secret'         => $secret,
            'services.stripe.webhook_secret' => 'whsec_prueba',
            'services.stripe.permitir_live'  => $permitir,
        ]);
        Cache::forget('stripe-live-bloqueado');
    }

    /** @return array<string, array{0: ?string, 1: ?string}> */
    public static function clavesLive(): array
    {
        return [
            'secreta sk_live'    => [null, 'sk_live_abc123'],
            'restringida rk_live' => [null, 'rk_live_abc123'],
            'publicable pk_live'  => ['pk_live_abc123', null],
            'pública live y secreta de prueba' => ['pk_live_abc123', 'sk_test_abc123'],
            'secreta live y pública de prueba' => ['pk_test_abc123', 'sk_live_abc123'],
            'con espacios alrededor' => [null, '  sk_live_abc123 '],
        ];
    }

    /** @dataProvider clavesLive */
    public function test_con_claves_live_y_sin_permiso_stripe_queda_desactivado(?string $key, ?string $secret): void
    {
        $this->claves($key, $secret);
        Log::shouldReceive('warning')->once()->withArgs(fn ($m) => str_contains($m, 'Stripe DESACTIVADO') && str_contains($m, 'STRIPE_PERMITIR_LIVE=true'));

        $servicio = new StripeService();
        $this->assertTrue($servicio->aplicarGuarda());
        $this->assertFalse($servicio->isEnabled());
        // Las claves se vacían: ni el webhook ni la pasarela real podrían usarlas.
        $this->assertNull(config('services.stripe.secret'));
        $this->assertNull(config('services.stripe.key'));
        $this->assertNull(config('services.stripe.webhook_secret'));
    }

    /** @dataProvider clavesLive */
    public function test_isenabled_tambien_rechaza_claves_live_aunque_no_se_haya_aplicado_la_guarda(?string $key, ?string $secret): void
    {
        $this->claves($key, $secret ?? 'sk_test_x');
        config(['services.stripe.key' => $key, 'services.stripe.secret' => $secret ?? 'sk_test_x']);

        $this->assertFalse((new StripeService())->isEnabled());
    }

    public function test_el_paso_3_usa_el_pago_simulado_y_no_ofrece_checkout(): void
    {
        $this->claves(null, 'sk_live_abc123');
        Log::shouldReceive('warning')->once();
        (new StripeService())->aplicarGuarda();

        $sala = Sala::factory()->create(['fk_cine_id' => Cine::factory()->create()->id, 'capacidad' => 50, 'disposicion_butacas' => 'standard']);
        $sesion = Sesion::factory()->create(['fk_pelicula_id' => Pelicula::factory()->create()->id, 'fk_sala_id' => $sala->id, 'fecha_hora' => now()->addDay(), 'preu_base' => 9]);
        $this->actingAs(User::factory()->create());
        $this->post(route('compra.step1.store'), ['sesion_id' => $sesion->id, 'entrades' => ['adult' => 1]]);
        $this->post(route('compra.step2.store'), ['butaques' => 'C1']);

        $this->get(route('compra.step3'))
            ->assertOk()
            ->assertSee('Pago simulado')
            ->assertSee('name="num_targeta"', false)
            ->assertDontSee(route('compra.stripe.checkout'), false);
        // Y el endpoint de Checkout devuelve al paso 3 sin crear ningún pago ni sesión de Stripe.
        $this->post(route('compra.stripe.checkout'), ['nom' => 'Ana', 'email' => 'ana@example.com'])->assertRedirect(route('compra.step3'));
        $this->assertSame(0, \App\Models\CheckoutPago::count());
    }

    public function test_el_webhook_no_acepta_nada_con_claves_live_bloqueadas(): void
    {
        $this->claves(null, 'sk_live_abc123');
        Log::shouldReceive('warning')->once();
        (new StripeService())->aplicarGuarda();

        $this->call('POST', '/stripe/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}')->assertStatus(400);
    }

    public function test_con_permiso_explicito_las_claves_live_se_respetan(): void
    {
        $this->claves('pk_live_abc123', 'sk_live_abc123', true);
        Log::shouldReceive('warning')->never();

        $servicio = new StripeService();
        $this->assertFalse($servicio->aplicarGuarda());
        $this->assertTrue($servicio->isEnabled());
        $this->assertSame('sk_live_abc123', config('services.stripe.secret'));
    }

    public function test_el_permiso_se_lee_como_booleano(): void
    {
        foreach (['true', '1', true] as $permitir) {
            $this->claves(null, 'sk_live_abc123', $permitir);
            $this->assertFalse((new StripeService())->liveBloqueado(), 'permitido con '.var_export($permitir, true));
        }
        foreach (['false', '0', '', null, false] as $permitir) {
            $this->claves(null, 'sk_live_abc123', $permitir);
            $this->assertTrue((new StripeService())->liveBloqueado(), 'bloqueado con '.var_export($permitir, true));
        }
    }

    public function test_las_claves_de_prueba_siguen_funcionando(): void
    {
        $this->claves('pk_test_abc123', 'sk_test_abc123');
        Log::shouldReceive('warning')->never();

        $servicio = new StripeService();
        $this->assertFalse($servicio->aplicarGuarda());
        $this->assertTrue($servicio->isEnabled());
    }

    public function test_el_aviso_no_se_repite_en_cada_peticion(): void
    {
        $this->claves(null, 'sk_live_abc123');
        Log::shouldReceive('warning')->once();

        (new StripeService())->aplicarGuarda();
        $this->claves(null, 'sk_live_abc123'); // las claves vuelven, pero la caché de «ya avisado» sigue
        Cache::add('stripe-live-bloqueado', true, now()->addMinutes(10));
        (new StripeService())->aplicarGuarda();
    }
}
