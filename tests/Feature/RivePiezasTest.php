<?php

namespace Tests\Feature;

use App\Models\Cine;
use App\Models\Pelicula;
use App\Models\Sala;
use App\Models\Sesion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Piezas de Rive (sesión 11): logotipo animado, ilustración de «Cómo se compra» y anillo de la cuenta atrás.
 * Solo se comprueba el marcado y los archivos; el comportamiento en el navegador lo cubre tests/browser/rive.spec.mjs.
 */
class RivePiezasTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_riv_construidos_y_el_runtime_estan_en_public_rive(): void
    {
        foreach (['logo.riv', 'compra.riv', 'anillo.riv'] as $archivo) {
            $ruta = public_path("rive/{$archivo}");
            $this->assertFileExists($ruta);
            $this->assertSame('RIVE', substr(file_get_contents($ruta), 0, 4), "{$archivo} es un archivo de Rive");
            $this->assertLessThan(40 * 1024, filesize($ruta), "{$archivo} pesa poco");
        }
        // El WebAssembly del runtime se aloja aquí para no depender de ningún CDN.
        $this->assertFileExists(public_path('rive/rive.wasm'));
    }

    public function test_las_fuentes_de_rive_son_solo_rml_sin_scripts(): void
    {
        $this->assertNotEmpty(glob(base_path('rive/*/*.rml')));
        $this->assertSame([], glob(base_path('rive/*/*.luau')));
        $this->assertSame([], glob(base_path('rive/*/*.as')));
        $this->assertSame([], glob(base_path('rive/*/*.wgsl')));
        foreach (glob(base_path('rive/*/*.rml')) as $rml) {
            $this->assertStringNotContainsString('<ScriptAsset', file_get_contents($rml), basename($rml).' no lleva scripts');
        }
    }

    public function test_la_portada_pinta_el_logotipo_con_rive_y_el_svg_de_reserva(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-rive="logo"', $html);
        $this->assertStringContainsString('rive/logo.riv', $html);
        $this->assertMatchesRegularExpression('/<canvas class="rive-logo-lienzo" aria-hidden="true"/', $html);
        // El símbolo SVG sigue dentro: es el reposo y la reserva si el runtime no carga.
        $this->assertMatchesRegularExpression('/data-rive="logo".*?<svg viewBox="0 0 64 64"/s', $html);
        // El nombre accesible del enlace no cambia.
        $this->assertStringContainsString('aria-label="'.config('app.name').', ir a la portada"', $html);
    }

    public function test_la_ilustracion_de_como_se_compra_es_decorativa_y_conserva_las_vistas_html(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-vista-rive', $html);
        $this->assertStringContainsString('rive/compra.riv', $html);
        $this->assertMatchesRegularExpression('/data-escena-compra aria-hidden="true"/', $html);
        // Las vistas HTML de los pasos 2 a 4 siguen, para móvil, movimiento reducido y si el runtime falla.
        foreach ([0, 1, 2, 3] as $n) {
            $this->assertStringContainsString('data-vista="'.$n.'"', $html);
        }
        // El elemento que se fija va a todo el ancho; el contenedor centrado es otro.
        $this->assertMatchesRegularExpression('/class="compra-pista" data-compra-pista>\s*<div class="compra-rejilla">/', $html);
    }

    public function test_las_paginas_que_no_son_portada_ni_marca_no_pintan_el_logotipo_con_rive(): void
    {
        $this->get('/peliculas')->assertOk()->assertDontSee('data-rive="logo"', false);
    }

    public function test_marca_anima_el_logotipo_grande(): void
    {
        $this->get('/marca')->assertOk()->assertSee('data-rive="logo"', false);
    }

    public function test_el_paso_2_lleva_el_anillo_decorativo_y_la_cuenta_atras_sigue_en_html(): void
    {
        $sala = Sala::factory()->create(['fk_cine_id' => Cine::factory()->create()->id, 'capacidad' => 50, 'disposicion_butacas' => 'standard']);
        $sesion = Sesion::factory()->create(['fk_pelicula_id' => Pelicula::factory()->create()->id, 'fk_sala_id' => $sala->id, 'fecha_hora' => now()->addDay(), 'preu_base' => 9]);
        $this->actingAs(User::factory()->create());
        $this->post(route('compra.step1.store'), ['sesion_id' => $sesion->id, 'entrades' => ['adult' => 1]]);

        $html = $this->get(route('compra.step2'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<span class="anillo" data-rive="anillo" data-rive-src="[^"]*rive\/anillo\.riv" aria-hidden="true">/', $html);
        $this->assertStringContainsString('data-total="480"', $html);
        // El número y el aviso para lectores de pantalla siguen siendo HTML.
        $this->assertStringContainsString('id="cuenta-atras-valor"', $html);
        $this->assertStringContainsString('role="timer"', $html);
        $this->assertStringContainsString('aria-live="polite" id="cuenta-atras-aviso"', $html);
        // El mapa de butacas no se ha tocado: sigue siendo una rejilla accesible.
        $this->assertStringContainsString('role="grid"', $html);
    }
}
