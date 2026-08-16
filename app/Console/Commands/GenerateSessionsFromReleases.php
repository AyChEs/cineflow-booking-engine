<?php

namespace App\Console\Commands;

use App\Models\Categoria;
use App\Models\Pelicula;
use App\Models\Sala;
use App\Models\Sesion;
use App\Services\DevsApiHubMovieService;
use App\Services\YelmoCarteleraService;
use Illuminate\Console\Command;

/**
 * Sincroniza la cartelera local con la taquilla española en tiempo real.
 *
 * Fuente: endpoint now_playing de TMDB (region=ES). Por cada estreno vigente:
 *   - Reutiliza la película en BD o la crea con metadatos y categorías mapeadas
 *     desde los IDs de género de TMDB.
 *   - Distribuye sesiones en 7 días sobre todas las salas, variando horarios
 *     (mañana, tarde, noche) para acercarse al ritmo real de un cine.
 *
 * Se invoca manualmente o desde el scheduler cada 6 horas.
 */
class GenerateSessionsFromReleases extends Command
{
    protected $signature   = 'sesions:generate-from-releases';
    protected $description = 'Crea sesiones para estrenos en taquilla española (TMDB now_playing)';

    /**
     * Mapeo ID género TMDB → nombre de categoría local.
     * Referencia oficial: https://developer.themoviedb.org/reference/genre-movie-list
     */
    private const GENRE_MAP = [
        28    => 'Acción',
        12    => 'Aventura',
        16    => 'Animación',
        35    => 'Comedia',
        80    => 'Crimen',
        99    => 'Documental',
        18    => 'Drama',
        10751 => 'Aventura',
        14    => 'Fantasía',
        36    => 'Histórica',
        27    => 'Terror',
        10402 => 'Musical',
        9648  => 'Suspense',
        10749 => 'Romance',
        878   => 'Ciencia Ficción',
        53    => 'Suspense',
        10752 => 'Bélica',
        37    => 'Histórica',
    ];

    /**
     * Franjas horarias realistas — una función de sala+día elige una combinación
     * estable (pseudoaleatoria pero determinista) para no duplicar sesiones.
     */
    private const TIME_SLOTS = ['11:30', '13:45', '16:00', '17:30', '19:15', '20:00', '21:30', '22:45'];

    public function handle(DevsApiHubMovieService $api, YelmoCarteleraService $yelmo): int
    {
        if (! $api->isEnabled()) {
            $this->warn('La API de TMDB está desactivada o sin clave. Saliendo.');
            return Command::FAILURE;
        }

        // Fuente de cartelera: si Yelmo está activo, espejamos exactamente sus
        // títulos (emparejados con TMDB). Si no, usamos now_playing de TMDB.
        if ($yelmo->isEnabled()) {
            $movies = $this->billboardFromYelmo($api, $yelmo);
        } else {
            $movies = $api->getNowPlaying();
        }

        $salas  = Sala::all();

        if ($salas->isEmpty()) {
            $this->warn('No hay salas registradas en la base de datos.');
            return Command::FAILURE;
        }

        if (empty($movies)) {
            $this->warn('TMDB no devolvió estrenos para España en este momento.');
            return Command::SUCCESS;
        }

        // Cacheamos categorías por nombre para evitar N+1 al mapear géneros.
        $categoriasByName = Categoria::pluck('id', 'nombre')->all();

        $this->info('Estrenos recibidos de TMDB: ' . count($movies));
        $created        = 0;
        $tmdbIdsActivos = array_column($movies, 'id');

        foreach ($movies as $movie) {
            $tmdbId = (string) $movie['id'];

            // Busca primero por tmdb_id (exacto), luego fallback por título difuso.
            $pelicula = Pelicula::where('tmdb_id', $tmdbId)->first()
                ?? Pelicula::whereRaw('LOWER(titulo) LIKE ?', [
                    '%' . strtolower($movie['title']) . '%',
                ])->first();

            if (! $pelicula) {
                $pelicula = Pelicula::create([
                    'titulo'             => $movie['title'],
                    'sinopsis'           => $movie['description'] ?? null,
                    'poster_path'        => $movie['image_url'] ?? null,
                    'duracion_min'       => 100,
                    'classificacio_edad' => null,
                    'tmdb_id'            => $tmdbId,
                    'rating'             => $movie['stars'] ?? null,
                ]);
                $this->line("  → Película creada: {$pelicula->titulo}");
            } else {
                // Sincroniza tmdb_id (si se encontró por título) y refresca la
                // valoración con el último dato de TMDB.
                $cambios = [];
                if ($pelicula->tmdb_id !== $tmdbId) {
                    $cambios['tmdb_id'] = $tmdbId;
                }
                if (isset($movie['stars']) && $pelicula->rating != $movie['stars']) {
                    $cambios['rating'] = $movie['stars'];
                }
                if (! empty($cambios)) {
                    $pelicula->update($cambios);
                }
            }

            // Asignamos categorías a partir de los IDs de género de TMDB. El
            // syncWithoutDetaching preserva manualmente añadidas por admin.
            $categoriaIds = $this->mapGenresToCategoriaIds($movie['genre_ids'] ?? [], $categoriasByName);
            if (!empty($categoriaIds)) {
                $pelicula->categorias()->syncWithoutDetaching($categoriaIds);
            }

            // Programamos sesiones para los próximos 7 días en cada sala con
            // horarios rotados para dar sensación de cartelera real.
            foreach ($salas as $salaIdx => $sala) {
                for ($dia = 1; $dia <= 7; $dia++) {
                    // Dos pases por día y sala: uno en tarde y otro en noche.
                    foreach ([0, 1] as $pase) {
                        $slotIdx  = ($pelicula->id + $sala->id + $dia + $pase * 3) % count(self::TIME_SLOTS);
                        $hora     = self::TIME_SLOTS[$slotIdx];
                        $fechaHora = now()->addDays($dia)->setTimeFromTimeString($hora);

                        $existe = Sesion::where('fk_pelicula_id', $pelicula->id)
                            ->where('fk_sala_id', $sala->id)
                            ->where('fecha_hora', $fechaHora)
                            ->exists();

                        if (! $existe) {
                            Sesion::create([
                                'fk_pelicula_id' => $pelicula->id,
                                'fk_sala_id'     => $sala->id,
                                'fecha_hora'     => $fechaHora,
                                'preu_base'      => $this->priceForSlot($hora),
                            ]);
                            $created++;
                        }
                    }
                }
            }
        }

        $this->info("Sesiones creadas: {$created}");

        // Elimina películas que ya no están en la taquilla española.
        // Solo borra las que tienen tmdb_id (gestionadas por este comando) y
        // no tienen reservas activas (protege datos de clientes).
        $fuera = Pelicula::whereNotNull('tmdb_id')
            ->whereNotIn('tmdb_id', array_map('strval', $tmdbIdsActivos))
            ->get();

        $eliminadas = 0;
        foreach ($fuera as $p) {
            $tieneReservas = \App\Models\Reserva::whereHas('sesion', fn($q) => $q->where('fk_pelicula_id', $p->id))->exists();
            if ($tieneReservas) {
                $this->warn("  Película con reservas activas, no se elimina: {$p->titulo}");
                continue;
            }
            // Borra sesiones futuras sin reservas y luego la película
            Sesion::where('fk_pelicula_id', $p->id)
                ->where('fecha_hora', '>', now())
                ->doesntHave('reservas')
                ->delete();
            $p->delete();
            $this->line("  ✗ Retirada de cartelera: {$p->titulo}");
            $eliminadas++;
        }

        if ($eliminadas > 0) {
            $this->info("Películas retiradas de cartelera: {$eliminadas}");
        }

        return Command::SUCCESS;
    }

    /**
     * Construye la cartelera a partir de los títulos reales de Yelmo Cines,
     * emparejando cada uno con TMDB para conseguir póster/géneros/valoración.
     * El resultado tiene el mismo formato normalizado que getNowPlaying().
     */
    private function billboardFromYelmo(DevsApiHubMovieService $api, YelmoCarteleraService $yelmo): array
    {
        $titles = $yelmo->getTitles();
        if (empty($titles)) {
            $this->warn('Yelmo no devolvió cartelera; recurriendo a TMDB now_playing.');
            return $api->getNowPlaying();
        }

        $this->info('Títulos en cartelera de Yelmo: ' . count($titles));

        $movies = [];
        $seen   = [];
        $sinMatch = [];

        foreach ($titles as $item) {
            $match = $api->matchByTitle($item['title'], $item['original'] ?? null);
            if (! $match || empty($match['id'])) {
                $sinMatch[] = $item['title'];
                continue;
            }
            if (isset($seen[$match['id']])) {
                continue;
            }
            $seen[$match['id']] = true;
            $movies[] = $match;
        }

        $this->info('Emparejadas con TMDB: ' . count($movies));
        if (! empty($sinMatch)) {
            $this->line('  Sin coincidencia en TMDB (' . count($sinMatch) . '): ' . implode(', ', array_slice($sinMatch, 0, 10)));
        }

        return $movies;
    }

    /**
     * Traduce IDs de género TMDB a IDs de categoría local usando GENRE_MAP.
     * Los géneros sin equivalente se descartan silenciosamente.
     */
    private function mapGenresToCategoriaIds(array $genreIds, array $categoriasByName): array
    {
        $ids = [];
        foreach ($genreIds as $gid) {
            $nombre = self::GENRE_MAP[$gid] ?? null;
            if ($nombre && isset($categoriasByName[$nombre])) {
                $ids[] = $categoriasByName[$nombre];
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Precio diferenciado por franja: mañana más barata, prime time recargado.
     */
    private function priceForSlot(string $hora): float
    {
        $h = (int) substr($hora, 0, 2);
        if ($h < 14)  return 7.50;
        if ($h < 18)  return 8.50;
        if ($h < 21)  return 9.50;
        return 10.50;
    }
}
