<?php

namespace Tests\Feature;

use App\Models\Cine;
use App\Models\Pelicula;
use App\Models\Sala;
use App\Models\Sesion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarteleraViewTest extends TestCase
{
    use RefreshDatabase;

    private function peliculaConSesion(): array
    {
        $sala = Sala::factory()->create(['fk_cine_id' => Cine::factory()->create()->id]);
        $pelicula = Pelicula::factory()->create(['titulo' => 'La sala oscura']);
        $sesion = Sesion::factory()->create([
            'fk_pelicula_id' => $pelicula->id,
            'fk_sala_id'     => $sala->id,
            'fecha_hora'     => now()->addDays(2)->setTime(20, 30),
        ]);

        return [$pelicula, $sesion];
    }

    public function test_la_cartelera_anuncia_el_numero_de_peliculas(): void
    {
        $this->peliculaConSesion();

        $this->get('/peliculas')
            ->assertOk()
            ->assertSee('aria-live="polite"', false)
            ->assertSee('1 película')
            ->assertSee('role="search"', false)
            ->assertSee('La sala oscura');
    }

    public function test_la_ficha_enlaza_a_la_compra_de_cada_sesion(): void
    {
        [$pelicula, $sesion] = $this->peliculaConSesion();

        $this->get("/peliculas/{$pelicula->id}")
            ->assertOk()
            ->assertSee('/comprar?sesion_id='.$sesion->id, false)
            ->assertSee('Comprar para las 20:30', false);
    }

    public function test_la_ficha_sin_sesiones_lo_dice(): void
    {
        $pelicula = Pelicula::factory()->create();

        $this->get("/peliculas/{$pelicula->id}")
            ->assertOk()
            ->assertSee('No hay sesiones programadas');
    }

    public function test_la_ficha_del_cine_muestra_sus_sesiones(): void
    {
        [$pelicula, $sesion] = $this->peliculaConSesion();

        $this->get('/cines/'.$sesion->sala->fk_cine_id)
            ->assertOk()
            ->assertSee($pelicula->titulo)
            ->assertSee('/comprar?sesion_id='.$sesion->id, false);
    }
}
