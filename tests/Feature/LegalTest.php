<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Aviso legal, política de privacidad, casilla de consentimiento del registro y cookies (LSSI-CE, RGPD, LOPDGDD). */
class LegalTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_aviso_legal_dice_quien_es_y_que_todo_es_ficticio(): void
    {
        $this->get(route('legal.aviso'))
            ->assertOk()
            ->assertSee('Aviso legal')
            ->assertSee('proyecto final de ciclo de demostración')
            ->assertSee('Ayman Charoui Essamadi')
            ->assertSee('Ismael Achamrouk')
            ->assertSee('Danna Guevara')
            ->assertSee('sin actividad comercial', false)
            ->assertSee('No tiene actividad comercial')
            ->assertSee('ficticios')
            ->assertSee('Stripe en modo de pruebas')
            ->assertSee('mailto:aymanessamadi72@gmail.com', false);
    }

    public function test_la_privacidad_cubre_lo_que_exige_el_rgpd(): void
    {
        $html = $this->get(route('legal.privacidad'))->assertOk()->getContent();

        foreach ([
            'Responsable', 'Para qué se usan tus datos', 'Qué datos se guardan', 'Base legal', 'consentimiento',
            'Cuánto tiempo se conservan', 'Render', 'Stripe', 'cláusulas contractuales tipo', 'Transferencias internacionales',
            'acceso, rectificación, supresión, oposición, limitación', 'portabilidad', 'Agencia Española de Protección de Datos',
            'No uses datos reales', 'aymanessamadi72@gmail.com', 'cineflow-session', 'XSRF-TOKEN',
        ] as $texto) {
            $this->assertStringContainsStringIgnoringCase($texto, $html, "falta «{$texto}»");
        }
    }

    public function test_las_paginas_legales_no_inventan_datos_de_identificacion(): void
    {
        foreach ([route('legal.aviso'), route('legal.privacidad')] as $url) {
            $texto = strip_tags($this->get($url)->getContent());
            $this->assertDoesNotMatchRegularExpression('/\b\d{8}[A-Z]\b/', $texto, 'nada con forma de DNI');
            $this->assertDoesNotMatchRegularExpression('/\b[A-Z]\d{7}[A-Z0-9]\b/', $texto, 'nada con forma de CIF');
            $this->assertStringNotContainsString('—', $texto, 'sin rayas largas');
            $this->assertStringNotContainsString('!', $texto, 'sin exclamaciones');
        }
    }

    public function test_el_pie_enlaza_las_dos_paginas_en_toda_la_web(): void
    {
        foreach (['/', '/peliculas', '/cines', '/login'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee(route('legal.aviso'), false)
                ->assertSee(route('legal.privacidad'), false);
        }
    }

    public function test_el_registro_tiene_la_casilla_de_privacidad_con_su_enlace(): void
    {
        $html = $this->get('/register')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<input type="checkbox" name="privacidad" id="privacidad"[^>]*required/', $html);
        $this->assertStringContainsString('He leído la', $html);
        $this->assertStringContainsString('política de privacidad', $html);
        $this->assertStringContainsString(route('legal.privacidad'), $html);
    }

    public function test_sin_marcar_la_casilla_no_se_crea_la_cuenta(): void
    {
        $datos = ['name' => 'Ana', 'apellidos' => 'Prueba', 'email' => 'ana@example.com', 'password' => 'password', 'password_confirmation' => 'password'];

        $this->post('/register', $datos)->assertSessionHasErrors('privacidad');
        $this->post('/register', $datos + ['privacidad' => '0'])->assertSessionHasErrors('privacidad');
        $this->post('/register', $datos + ['privacidad' => ''])->assertSessionHasErrors('privacidad');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);

        // El error se ve en la página, junto a la casilla, y la casilla conserva el resto de lo escrito.
        $this->from('/register')->followingRedirects()->post('/register', $datos)
            ->assertSee('Marca «He leído la política de privacidad» para crear la cuenta.');
    }

    public function test_marcando_la_casilla_se_crea_la_cuenta(): void
    {
        $this->post('/register', ['name' => 'Ana', 'apellidos' => 'Prueba', 'email' => 'ana@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'privacidad' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'ana@example.com', 'rol' => 'cliente']);
    }

    public function test_las_cookies_salen_con_secure_samesite_lax_y_la_de_sesion_con_httponly(): void
    {
        config(['session.secure' => true]); // como en producción (render.yaml y APP_ENV=production)

        $r = $this->get('https://localhost/')->assertOk();
        $cookies = collect($r->headers->getCookies())->keyBy(fn ($c) => $c->getName());

        $sesion = $cookies->get(config('session.cookie'));
        $xsrf = $cookies->get('XSRF-TOKEN');
        $this->assertNotNull($sesion, 'cookie de sesión');
        $this->assertNotNull($xsrf, 'cookie XSRF');
        foreach ([$sesion, $xsrf] as $cookie) {
            $this->assertTrue($cookie->isSecure(), $cookie->getName().' es Secure');
            $this->assertSame('lax', strtolower((string) $cookie->getSameSite()), $cookie->getName().' es SameSite=Lax');
        }
        $this->assertTrue($sesion->isHttpOnly(), 'la de sesión es HttpOnly');
        // La de XSRF debe poder leerla el JavaScript de la web (Axios la envía en la cabecera): no es HttpOnly.
        $this->assertFalse($xsrf->isHttpOnly());
    }

    public function test_en_produccion_las_cookies_son_secure_por_defecto(): void
    {
        $this->assertTrue(str_contains(file_get_contents(base_path('config/session.php')), "env('APP_ENV') === 'production'"));
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
    }

    public function test_el_aviso_y_la_privacidad_no_cargan_movimiento_ni_scripts_de_terceros(): void
    {
        foreach ([route('legal.aviso'), route('legal.privacidad')] as $url) {
            $html = $this->get($url)->getContent();
            $this->assertStringNotContainsString('data-mov', $html);
            $this->assertDoesNotMatchRegularExpression('/<script[^>]+src="https?:\/\/(?!localhost)/', $html);
        }
    }
}
