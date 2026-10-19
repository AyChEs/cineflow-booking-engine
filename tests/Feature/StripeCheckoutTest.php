<?php

namespace Tests\Feature;

use App\Contracts\PasarelaPago;
use App\Models\Cine;
use App\Models\CheckoutPago;
use App\Models\Pelicula;
use App\Models\Reserva;
use App\Models\ReservaSeat;
use App\Models\Sala;
use App\Models\SeatLock;
use App\Models\Sesion;
use App\Models\StripeEvento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Doble de la pasarela: registra las llamadas y no sale a la red. */
class PasarelaFalsa implements PasarelaPago
{
    public array $sesiones = [];
    public array $expiradas = [];
    public array $reembolsos = [];

    public function crearSesionCheckout(array $params, string $idempotencyKey): array
    {
        $id = 'cs_test_'.(count($this->sesiones) + 1);
        $this->sesiones[] = ['id' => $id, 'params' => $params, 'idempotency_key' => $idempotencyKey];

        return ['id' => $id, 'url' => "https://checkout.stripe.test/{$id}"];
    }

    public function expirarSesion(string $sessionId): void
    {
        $this->expiradas[] = $sessionId;
    }

    public function reembolsar(string $paymentIntentId, string $idempotencyKey): void
    {
        $this->reembolsos[] = ['payment_intent' => $paymentIntentId, 'idempotency_key' => $idempotencyKey];
    }
}

/**
 * Stripe Checkout con webhook firmado (plan/specs/05-stripe.md). Sin red: la
 * pasarela es un doble y la firma del webhook se calcula de verdad.
 */
class StripeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const SECRETO = 'whsec_prueba_local';

    private PasarelaFalsa $pasarela;
    private User $user;
    private User $otro;
    private Sesion $sesion;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.secret'         => 'sk_test_doble',
            'services.stripe.webhook_secret' => self::SECRETO,
            'services.stripe.currency'       => 'eur',
        ]);
        $this->pasarela = new PasarelaFalsa();
        $this->app->instance(PasarelaPago::class, $this->pasarela);
        Mail::fake();

        $this->user = User::factory()->create();
        $this->otro = User::factory()->create();
        $sala = Sala::factory()->create(['fk_cine_id' => Cine::factory()->create()->id, 'capacidad' => 50, 'disposicion_butacas' => 'standard']);
        $this->sesion = Sesion::factory()->create([
            'fk_pelicula_id' => Pelicula::factory()->create(['titulo' => 'Prueba'])->id,
            'fk_sala_id'     => $sala->id,
            'fecha_hora'     => now()->addDay(),
            'preu_base'      => 9.99,
        ]);
    }

    /** Pasos 1 y 2 como $user: 2 adultos y 1 reducida en A1, A2, A3. Después pulsa «Pagar». */
    private function pagar(): CheckoutPago
    {
        $this->actingAs($this->user);
        $this->post(route('compra.step1.store'), [
            'sesion_id' => $this->sesion->id,
            'entrades'  => ['adult' => 2, 'reduit' => 1],
        ]);
        $this->post(route('compra.step2.store'), ['butaques' => 'A1,A2,A3']);

        $this->post(route('compra.stripe.checkout'), ['nom' => 'Ana', 'email' => 'ana@example.com'])
            ->assertStatus(303)
            ->assertRedirect('https://checkout.stripe.test/cs_test_1');

        return CheckoutPago::firstOrFail();
    }

    private function webhook(string $tipo, CheckoutPago $pago, array $extra = [], ?string $eventId = null, ?string $secreto = null)
    {
        $payload = json_encode([
            'id'     => $eventId ?? 'evt_'.uniqid(),
            'object' => 'event',
            'type'   => $tipo,
            'data'   => ['object' => array_merge([
                'id'             => $pago->stripe_session_id,
                'object'         => 'checkout.session',
                'payment_status' => 'paid',
                'payment_intent' => 'pi_prueba',
                'metadata'       => ['checkout_pago_id' => (string) $pago->id],
            ], $extra)],
        ]);
        $t = time();
        $firma = hash_hmac('sha256', "{$t}.{$payload}", $secreto ?? self::SECRETO);

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$t},v1={$firma}",
        ], $payload);
    }

    public function test_pagar_crea_el_pago_abierto_y_amplia_los_bloqueos(): void
    {
        $pago = $this->pagar();

        $this->assertSame(CheckoutPago::ABIERTO, $pago->estado);
        $this->assertSame(['A1', 'A2', 'A3'], $pago->butacas);
        // 9,99 € × 1,00 = 999 c (×2) y 9,99 € × 0,80 = 799 c (×1).
        $this->assertSame(999 * 2 + 799, $pago->total);

        $llamada = $this->pasarela->sesiones[0];
        $this->assertSame("checkout-pago-{$pago->id}", $llamada['idempotency_key']);
        $this->assertSame('payment', $llamada['params']['mode']);
        $this->assertSame((string) $pago->id, $llamada['params']['metadata']['checkout_pago_id']);
        $this->assertSame($pago->total, collect($llamada['params']['line_items'])->sum(fn ($l) => $l['price_data']['unit_amount'] * $l['quantity']));
        $this->assertSame($pago->expires_at->getTimestamp(), $llamada['params']['expires_at']);

        $locks = SeatLock::where('sesion_id', $this->sesion->id)->get();
        $this->assertCount(3, $locks);
        foreach ($locks as $lock) {
            $this->assertEquals($pago->expires_at->copy()->addMinutes(2)->getTimestamp(), $lock->expires_at->getTimestamp());
        }
    }

    public function test_el_total_del_paso_1_coincide_con_lo_que_se_cobra(): void
    {
        $pago = $this->pagar();

        $this->assertSame($pago->total, (int) round(session('compra.total') * 100));
    }

    public function test_webhook_con_firma_invalida_devuelve_400_y_no_cambia_nada(): void
    {
        $pago = $this->pagar();

        $this->webhook('checkout.session.completed', $pago, [], null, 'whsec_otro')->assertStatus(400);
        $this->call('POST', '/stripe/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}')->assertStatus(400);

        $this->assertSame(0, Reserva::count());
        $this->assertSame(0, StripeEvento::count());
        $this->assertSame(CheckoutPago::ABIERTO, $pago->fresh()->estado);
    }

    public function test_completed_crea_una_reserva_y_deja_el_pago_pagado(): void
    {
        $pago = $this->pagar();

        $this->webhook('checkout.session.completed', $pago)->assertOk();

        $pago->refresh();
        $this->assertSame(CheckoutPago::PAGADO, $pago->estado);
        $reserva = Reserva::findOrFail($pago->reserva_id);
        $this->assertSame('pagat', $reserva->estat);
        $this->assertEquals(27.97, (float) $reserva->total_pagat);
        $this->assertSame($this->user->id, $reserva->fk_usuario_id);
        $this->assertSame(['A1', 'A2', 'A3'], ReservaSeat::where('reserva_id', $reserva->id)->orderBy('butaca')->pluck('butaca')->all());
        $this->assertSame(0, SeatLock::count());
        Mail::assertSent(\App\Mail\EntradaConfirmada::class);
    }

    public function test_el_mismo_evento_dos_veces_crea_una_sola_reserva(): void
    {
        $pago = $this->pagar();

        $this->webhook('checkout.session.completed', $pago, [], 'evt_repetido')->assertOk();
        $this->webhook('checkout.session.completed', $pago, [], 'evt_repetido')->assertOk();

        $this->assertSame(1, Reserva::count());
        $this->assertSame(3, ReservaSeat::count());
        $this->assertSame(1, StripeEvento::count());
    }

    public function test_dos_eventos_distintos_del_mismo_pago_crean_una_sola_reserva(): void
    {
        $pago = $this->pagar();

        $this->webhook('checkout.session.completed', $pago)->assertOk();
        $this->webhook('checkout.session.async_payment_succeeded', $pago)->assertOk();

        $this->assertSame(1, Reserva::count());
        $this->assertSame(2, StripeEvento::count());
        $this->assertSame(CheckoutPago::PAGADO, $pago->fresh()->estado);
        $this->assertSame([], $this->pasarela->reembolsos);
    }

    public function test_completed_sin_cobrar_espera_al_evento_asincrono(): void
    {
        $pago = $this->pagar();

        $this->webhook('checkout.session.completed', $pago, ['payment_status' => 'unpaid'])->assertOk();
        $this->assertSame(0, Reserva::count());
        $this->assertSame(CheckoutPago::ABIERTO, $pago->fresh()->estado);

        $this->webhook('checkout.session.async_payment_succeeded', $pago)->assertOk();
        $this->assertSame(1, Reserva::count());
    }

    public function test_expired_libera_las_butacas_y_otra_persona_puede_cogerlas(): void
    {
        $pago = $this->pagar();

        $this->webhook('checkout.session.expired', $pago, ['payment_status' => 'unpaid'])->assertOk();

        $this->assertSame(CheckoutPago::CADUCADO, $pago->fresh()->estado);
        $this->assertSame(0, SeatLock::count());

        $this->actingAs($this->otro)
            ->postJson(route('seat.lock'), ['sesion_id' => $this->sesion->id, 'butaca' => 'A1'])
            ->assertJson(['ok' => true]);

        // Un «completed» tardío para un pago caducado no crea nada.
        $this->webhook('checkout.session.completed', $pago)->assertOk();
        $this->assertSame(0, Reserva::count());
    }

    public function test_async_payment_failed_libera_las_butacas(): void
    {
        $pago = $this->pagar();

        $this->webhook('checkout.session.async_payment_failed', $pago, ['payment_status' => 'unpaid'])->assertOk();

        $this->assertSame(CheckoutPago::FALLIDO, $pago->fresh()->estado);
        $this->assertSame(0, SeatLock::count());
    }

    public function test_si_la_butaca_ya_se_vendio_completed_reembolsa(): void
    {
        $pago = $this->pagar();
        $otra = Reserva::create(['fk_usuario_id' => $this->otro->id, 'fk_sesion_id' => $this->sesion->id, 'estat' => 'pagat', 'total_pagat' => 9.99]);
        ReservaSeat::create(['reserva_id' => $otra->id, 'sesion_id' => $this->sesion->id, 'butaca' => 'A2']);

        $this->webhook('checkout.session.completed', $pago, ['payment_intent' => 'pi_a_devolver'])->assertOk();

        $this->assertSame(CheckoutPago::REEMBOLSADO, $pago->fresh()->estado);
        $this->assertSame([['payment_intent' => 'pi_a_devolver', 'idempotency_key' => "reembolso-{$pago->id}"]], $this->pasarela->reembolsos);
        $this->assertSame(1, Reserva::count());
        $this->assertSame(0, SeatLock::count());
    }

    public function test_la_vuelta_nunca_crea_reservas(): void
    {
        $pago = $this->pagar();

        $this->get(route('compra.stripe.return', ['session_id' => $pago->stripe_session_id]))
            ->assertOk()
            ->assertSee('Confirmando tu pago');
        $this->getJson(route('compra.estado', $pago))->assertOk()->assertJson(['estado' => 'abierto', 'reserva_url' => null]);

        $this->assertSame(0, Reserva::count());
        $this->assertSame(CheckoutPago::ABIERTO, $pago->fresh()->estado);
    }

    public function test_con_el_pago_confirmado_el_estado_lleva_a_la_entrada(): void
    {
        $pago = $this->pagar();
        $this->webhook('checkout.session.completed', $pago)->assertOk();

        $this->getJson(route('compra.estado', $pago))
            ->assertOk()
            ->assertJson(['estado' => 'pagado', 'reserva_url' => route('compra.confirmacio')]);
        $this->get(route('compra.confirmacio'))->assertOk();
    }

    public function test_el_estado_de_un_pago_ajeno_devuelve_403(): void
    {
        $pago = $this->pagar();

        $this->actingAs($this->otro)->getJson(route('compra.estado', $pago))->assertForbidden();
        $this->actingAs($this->otro)->get(route('compra.stripe.return', ['session_id' => $pago->stripe_session_id]))->assertForbidden();
    }

    public function test_cancelar_expira_la_sesion_y_libera_las_butacas(): void
    {
        $pago = $this->pagar();

        $this->get(route('compra.stripe.cancel'))->assertRedirect(route('compra.step2'));

        $this->assertSame(CheckoutPago::CADUCADO, $pago->fresh()->estado);
        $this->assertSame([$pago->stripe_session_id], $this->pasarela->expiradas);
        $this->assertSame(0, SeatLock::count());
    }

    public function test_la_limpieza_caduca_los_pagos_abandonados(): void
    {
        $pago = $this->pagar();
        $this->travel(\App\Services\CheckoutService::MINUTOS_CHECKOUT + 8)->minutes();

        $this->artisan('checkout:limpiar')->assertSuccessful();

        $this->assertSame(CheckoutPago::CADUCADO, $pago->fresh()->estado);
        $this->assertSame(0, SeatLock::where('sesion_id', $this->sesion->id)->where('user_id', $this->user->id)->count());
    }

    public function test_con_stripe_activo_no_se_acepta_el_pago_simulado(): void
    {
        $this->pagar();

        $this->post(route('compra.step3.store'), [
            'nom' => 'Ana', 'email' => 'ana@example.com', 'metode' => 'targeta',
            'num_targeta' => '4242424242424242', 'titular_targeta' => 'ANA', 'expiry_targeta' => '12/40', 'cvv_targeta' => '123',
        ])->assertRedirect(route('compra.step3'));

        $this->assertSame(0, Reserva::count());
    }

    public function test_butaca_cogida_por_otro_antes_de_pagar_vuelve_al_paso_2(): void
    {
        $this->actingAs($this->user);
        $this->post(route('compra.step1.store'), ['sesion_id' => $this->sesion->id, 'entrades' => ['adult' => 1]]);
        $this->post(route('compra.step2.store'), ['butaques' => 'B5']);
        SeatLock::where('butaca', 'B5')->update(['user_id' => $this->otro->id, 'session_token' => 'otra']);

        $this->post(route('compra.stripe.checkout'), ['nom' => 'Ana', 'email' => 'ana@example.com'])
            ->assertRedirect(route('compra.step2'));

        $this->assertSame([], $this->pasarela->sesiones);
    }

    public function test_con_stripe_el_paso_3_envia_a_checkout(): void
    {
        $this->actingAs($this->user);
        $this->post(route('compra.step1.store'), ['sesion_id' => $this->sesion->id, 'entrades' => ['adult' => 1]]);
        $this->post(route('compra.step2.store'), ['butaques' => 'C1']);

        $this->get(route('compra.step3'))
            ->assertOk()
            ->assertSee(route('compra.stripe.checkout'), false)
            ->assertSee('4242 4242 4242 4242')
            ->assertDontSee('name="num_targeta"', false);
    }

    public function test_sin_secreto_de_webhook_el_paso_3_muestra_el_pago_simulado(): void
    {
        config(['services.stripe.webhook_secret' => null]);
        $this->actingAs($this->user);
        $this->post(route('compra.step1.store'), ['sesion_id' => $this->sesion->id, 'entrades' => ['adult' => 1]]);
        $this->post(route('compra.step2.store'), ['butaques' => 'C1']);

        $this->get(route('compra.step3'))
            ->assertOk()
            ->assertSee('name="num_targeta"', false)
            ->assertDontSee(route('compra.stripe.checkout'), false);
    }
}
