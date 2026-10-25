<?php

namespace Tests\Feature;

use App\Models\Cine;
use App\Models\Pelicula;
use App\Models\Sala;
use App\Models\Sesion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Cabeceras de seguridad (middleware CabecerasDeSeguridad, config/seguridad.php). */
class CabecerasDeSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private function csp(string $url = '/'): string
    {
        return (string) $this->get($url)->assertOk()->headers->get('Content-Security-Policy');
    }

    public function test_toda_pagina_lleva_las_cabeceras_basicas(): void
    {
        $r = $this->get('/')->assertOk();

        $r->assertHeader('X-Content-Type-Options', 'nosniff');
        $r->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $r->assertHeader('X-Frame-Options', 'DENY');
        $r->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $this->assertStringContainsString('camera=()', $r->headers->get('Permissions-Policy'));
        $this->assertStringContainsString('geolocation=()', $r->headers->get('Permissions-Policy'));
    }

    public function test_hsts_solo_en_https(): void
    {
        $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');

        $r = $this->get('https://localhost/')->assertOk();
        $this->assertSame('max-age=15552000', $r->headers->get('Strict-Transport-Security'));
        $this->assertStringContainsString('upgrade-insecure-requests', $r->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString('upgrade-insecure-requests', $this->csp('http://localhost/'));
    }

    public function test_la_csp_permite_stripe_tmdb_y_el_wasm_de_rive(): void
    {
        $csp = $this->csp();

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("'wasm-unsafe-eval'", $csp);
        $this->assertMatchesRegularExpression("/img-src [^;]*https:\/\/image\.tmdb\.org/", $csp);
        $this->assertStringContainsString("form-action 'self' https://checkout.stripe.com", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-src 'none'", $csp);
        // Nada de «unsafe-inline» en scripts: los en línea llevan nonce.
        $this->assertDoesNotMatchRegularExpression("/script-src [^;]*'unsafe-inline'/", $csp);
    }

    public function test_los_scripts_en_linea_y_los_de_vite_llevan_el_nonce_de_la_peticion(): void
    {
        $r = $this->get('/')->assertOk();
        preg_match("/'nonce-([^']+)'/", $r->headers->get('Content-Security-Policy'), $m);
        $this->assertNotEmpty($m[1], 'la CSP trae un nonce');

        $html = $r->getContent();
        $this->assertStringContainsString('<script nonce="'.$m[1].'">', $html, 'el script en línea del layout');
        // Todos los <script> de la página (en línea o de Vite) llevan el mismo nonce.
        preg_match_all('/<script\b([^>]*)>/', $html, $scripts);
        $this->assertNotEmpty($scripts[1]);
        foreach ($scripts[1] as $atributos) {
            $this->assertStringContainsString('nonce="'.$m[1].'"', $atributos, "script sin nonce: <script{$atributos}>");
        }
    }

    public function test_el_nonce_cambia_en_cada_peticion(): void
    {
        preg_match("/'nonce-([^']+)'/", $this->csp(), $a);
        preg_match("/'nonce-([^']+)'/", $this->csp(), $b);

        $this->assertNotSame($a[1], $b[1]);
    }

    public function test_unsafe_eval_solo_donde_hay_alpine_con_expresiones(): void
    {
        // Portada: sin x-data, sin unsafe-eval.
        $this->assertStringNotContainsString("'unsafe-eval'", $this->csp('/'));

        // Con sesión iniciada el menú de usuario lleva x-data: ahí sí.
        $this->actingAs(User::factory()->create());
        $conAlpine = $this->get('/')->assertOk();
        if (str_contains($conAlpine->getContent(), 'x-data')) {
            $this->assertStringContainsString("'unsafe-eval'", $conAlpine->headers->get('Content-Security-Policy'));
        } else {
            $this->assertStringNotContainsString("'unsafe-eval'", $conAlpine->headers->get('Content-Security-Policy'));
        }
    }

    public function test_la_camara_solo_se_permite_en_la_taquilla(): void
    {
        $this->actingAs(User::factory()->taquilla()->create());
        $r = $this->get(route('taquilla.scanner'))->assertOk();
        $this->assertStringContainsString('camera=(self)', $r->headers->get('Permissions-Policy'));

        $this->assertStringContainsString('camera=()', $this->get('/')->headers->get('Permissions-Policy'));
    }

    public function test_no_se_anuncia_la_version_de_php(): void
    {
        $this->assertNull($this->get('/')->headers->get('X-Powered-By'));
    }

    public function test_se_puede_desactivar_con_la_configuracion(): void
    {
        config(['seguridad.cabeceras' => false]);

        $this->get('/')->assertOk()->assertHeaderMissing('Content-Security-Policy')->assertHeaderMissing('X-Frame-Options');
    }

    public function test_el_pago_con_stripe_sigue_redirigiendo_a_checkout(): void
    {
        // La CSP no impide la redirección: form-action incluye checkout.stripe.com (comprobado arriba) y el flujo sigue igual.
        config(['services.stripe.secret' => 'sk_test_doble', 'services.stripe.webhook_secret' => 'whsec_x']);
        $sala = Sala::factory()->create(['fk_cine_id' => Cine::factory()->create()->id, 'capacidad' => 50, 'disposicion_butacas' => 'standard']);
        $sesion = Sesion::factory()->create(['fk_pelicula_id' => Pelicula::factory()->create()->id, 'fk_sala_id' => $sala->id, 'fecha_hora' => now()->addDay(), 'preu_base' => 9]);
        $this->actingAs(User::factory()->create());
        $this->post(route('compra.step1.store'), ['sesion_id' => $sesion->id, 'entrades' => ['adult' => 1]]);
        $this->post(route('compra.step2.store'), ['butaques' => 'C1']);

        $r = $this->get(route('compra.step3'))->assertOk();
        $this->assertStringContainsString('https://checkout.stripe.com', $r->headers->get('Content-Security-Policy'));
        $r->assertSee(route('compra.stripe.checkout'), false);
    }

    public function test_las_vistas_no_llevan_manejadores_en_linea(): void
    {
        // La CSP bloquea onclick="…", onerror="…", etc. Los comportamientos van en resources/js/interacciones.js con data-*.
        $encontrados = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $archivo) {
            if (! $archivo->isFile() || ! str_ends_with($archivo->getFilename(), '.blade.php')) {
                continue;
            }
            // Solo atributos de etiquetas HTML (<… onclick="…">), no los comentarios de los scripts.
            if (preg_match_all('/<[a-z][^>]*\s(on(?:error|load|click|change|submit|input|focus|blur|keydown|keyup|mouseover|mouseout))\s*=\s*["\']/i', file_get_contents($archivo->getPathname()), $m)) {
                $encontrados[] = str_replace(resource_path('views').'/', '', $archivo->getPathname()).': '.implode(', ', $m[1]);
            }
        }

        $this->assertSame([], $encontrados, "Manejadores en línea:\n".implode("\n", $encontrados));
    }
}
