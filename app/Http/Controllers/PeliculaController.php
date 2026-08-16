<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Pelicula;
use App\Models\Categoria;
use App\Models\Cine;
use App\Models\Sesion;
use App\Services\DevsApiHubMovieService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeliculaController extends Controller
{
    /**
     * Cartelera pública. Solo muestra películas con sesiones futuras programadas,
     * siguiendo el modelo de cines tipo Yelmo/Ocine: si no hay sesión, no sale.
     * Filtros opcionales: día, cine, categoría, franja horaria.
     */
    public function index(Request $request, DevsApiHubMovieService $moviesApi)
    {
        $filterDates = Sesion::selectRaw('DATE(fecha_hora) as dia')
            ->where('fecha_hora', '>=', now())
            ->distinct()->orderBy('dia')->pluck('dia');
        $filterCines = Cine::orderBy('nombre')->get()->unique('nombre')->values();
        $filterCats  = Categoria::orderBy('nombre')->get();

        // Base: solo películas con al menos una sesión futura
        $query = Pelicula::with('categorias')
            ->whereHas('sesiones', fn($q) => $q->where('fecha_hora', '>=', now()));

        // Con la API de TMDB activa, la cartelera refleja SOLO la taquilla
        // española en vivo (películas sincronizadas con tmdb_id). Sin clave
        // (modo demo/tests) se muestran las películas del seeder.
        if ($moviesApi->isEnabled()) {
            $query->whereNotNull('tmdb_id');
        }

        // Búsqueda por título (case-insensitive).
        if ($request->filled('q')) {
            $termino = trim($request->input('q'));
            $query->whereRaw('LOWER(titulo) LIKE ?', ['%' . strtolower($termino) . '%']);
        }

        if ($request->filled('dia')) {
            $query->whereHas('sesiones', fn($q) =>
                $q->whereDate('fecha_hora', $request->dia)
                  ->where('fecha_hora', '>=', now())
            );
        }

        if ($request->filled('cine_id')) {
            $query->whereHas('sesiones.sala', fn($q) =>
                $q->where('fk_cine_id', $request->cine_id)
            );
        }

        if ($request->filled('categoria_id')) {
            $query->whereHas('categorias', fn($q) =>
                $q->where('categorias.id', $request->categoria_id)
            );
        }

        $horaDesde = (int) $request->input('hora_desde', 0);
        $horaHasta = (int) $request->input('hora_hasta', 23);
        if ($horaDesde > 0 || $horaHasta < 23) {
            $query->whereHas('sesiones', fn($q) =>
                $q->whereTime('fecha_hora', '>=', sprintf('%02d:00', $horaDesde))
                  ->whereTime('fecha_hora', '<=', sprintf('%02d:59', $horaHasta))
            );
        }

        // Ordenación. Por defecto, mejor valoradas primero (nulls al final).
        $orden = $request->input('orden', 'rating');
        match ($orden) {
            'titulo'   => $query->orderBy('titulo'),
            'durada'   => $query->orderByRaw('duracion_min IS NULL, duracion_min ASC'),
            default    => $query->orderByRaw('rating IS NULL, rating DESC')->orderBy('titulo'),
        };

        $peliculas = $query->get();

        // Conteo de filtros activos (excluye la ordenación) para la UI.
        $activeFilters = collect(['q', 'dia', 'cine_id', 'categoria_id'])
            ->filter(fn($f) => $request->filled($f))
            ->count()
            + (($horaDesde > 0 || $horaHasta < 23) ? 1 : 0);

        $filtered = $activeFilters > 0;

        return view('peliculas.index', compact(
            'peliculas',
            'filterDates',
            'filterCines',
            'filterCats',
            'filtered',
            'activeFilters',
            'orden'
        ));
    }

    /**
        * Cartelera desde API externa (TMDB).
     */
    public function externalIndex(Request $request, DevsApiHubMovieService $moviesApi)
    {
        $movies = [];
        $activeFilter = 'all';

        // Solo consultamos TMDB si la API está activa y tiene key configurada.
        if ($moviesApi->isEnabled()) {
            // Orden de prioridad de filtros: género, año, estrellas, límite y fallback a listado general.
            if ($request->filled('genre')) {
                $activeFilter = 'genre';
                $movies = $moviesApi->getByGenre((string) $request->input('genre'));
            } elseif ($request->filled('year')) {
                $activeFilter = 'year';
                $movies = $moviesApi->getByYear((int) $request->input('year'));
            } elseif ($request->filled('stars')) {
                $activeFilter = 'stars';
                $movies = $moviesApi->getByStars((string) $request->input('stars'));
            } elseif ($request->filled('limit')) {
                $activeFilter = 'limit';
                $movies = $moviesApi->getByLimit((int) $request->input('limit'));
            } else {
                $movies = $moviesApi->getAll();
            }
        }

        return view('peliculas.external-index', [
            'movies' => $movies,
            'apiEnabled' => $moviesApi->isEnabled(),
            'activeFilter' => $activeFilter,
        ]);
    }

    /**
     * Detalle de película desde API externa.
     */
    public function externalShow(int $id, DevsApiHubMovieService $moviesApi)
    {
        // Si TMDB no está disponible evitamos exponer una vista vacía.
        abort_unless($moviesApi->isEnabled(), 404);

        $movie = $moviesApi->getById($id);
        // Si TMDB no devuelve la película, mantenemos comportamiento estándar 404.
        abort_if(! $movie, 404);

        return view('peliculas.external-show', [
            'movie' => $movie,
        ]);
    }

    /**
     * Formulario para crear pelicula en API externa.
     */
    public function externalCreate(DevsApiHubMovieService $moviesApi)
    {
        abort_unless($moviesApi->isEnabled(), 404);

        return view('peliculas.external-form', [
            'mode' => 'create',
            'movie' => null,
        ]);
    }

    /**
     * Guarda pelicula en API externa.
     */
    public function externalStore(Request $request, DevsApiHubMovieService $moviesApi)
    {
        abort_unless($moviesApi->isEnabled(), 404);

        if (! $moviesApi->canWrite()) {
            return back()->with('error', 'TMDB does not allow creating movies through this integration.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'year' => 'nullable|integer|min:1900|max:2100',
            'image_url' => 'nullable|url|max:2048',
            'genre' => 'nullable|string|max:80',
            'stars' => 'nullable|numeric|min:0|max:5',
        ]);

        $created = $moviesApi->createMovie($this->buildExternalPayload($validated));

        if (! $created) {
            return back()->withInput()->with('error', 'Could not create the movie in the external API.');
        }

        return redirect()->route('peliculas.external.index')->with('success', 'Movie created in the external API.');
    }

    /**
     * Formulario para editar pelicula en API externa.
     */
    public function externalEdit(int $id, DevsApiHubMovieService $moviesApi)
    {
        abort_unless($moviesApi->isEnabled(), 404);

        $movie = $moviesApi->getById($id);
        abort_if(! $movie, 404);

        return view('peliculas.external-form', [
            'mode' => 'edit',
            'movie' => $movie,
        ]);
    }

    /**
     * Actualiza pelicula en API externa.
     */
    public function externalUpdate(int $id, Request $request, DevsApiHubMovieService $moviesApi)
    {
        abort_unless($moviesApi->isEnabled(), 404);

        if (! $moviesApi->canWrite()) {
            return back()->with('error', 'TMDB does not allow editing movies through this integration.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'year' => 'nullable|integer|min:1900|max:2100',
            'image_url' => 'nullable|url|max:2048',
            'genre' => 'nullable|string|max:80',
            'stars' => 'nullable|numeric|min:0|max:5',
        ]);

        $payload = $this->buildExternalPayload($validated);
        $payload['id'] = $id;

        $updated = $moviesApi->updateMovie($id, $payload);

        if (! $updated) {
            return back()->withInput()->with('error', 'Could not update the movie in the external API.');
        }

        return redirect()->route('peliculas.external.index')->with('success', 'Movie updated in the external API.');
    }

    /**
     * Elimina pelicula en API externa.
     */
    public function externalDestroy(int $id, DevsApiHubMovieService $moviesApi)
    {
        abort_unless($moviesApi->isEnabled(), 404);

        if (! $moviesApi->canWrite()) {
            return redirect()->route('peliculas.external.index')
                ->with('error', 'TMDB does not allow deleting movies through this integration.');
        }

        $ok = $moviesApi->deleteMovie($id);

        return redirect()->route('peliculas.external.index')
            ->with($ok ? 'success' : 'error', $ok
                ? 'Movie deleted in the external API.'
                : 'Could not delete the movie in the external API.');
    }

    /**
     * Sincroniza la cartelera local con peliculas de la API externa.
     */
    public function syncExternalCatalog(DevsApiHubMovieService $moviesApi)
    {
        abort_unless($moviesApi->isEnabled(), 404);

        // Cargamos cartelera externa completa para crear/actualizar catálogo local.
        $externalMovies = $moviesApi->getAll();
        if (empty($externalMovies)) {
            return redirect()->route('peliculas.external.index')
                ->with('error', 'Could not fetch movies from TMDB to sync. Check TMDB_API_KEY.');
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($externalMovies, &$created, &$updated) {
            foreach ($externalMovies as $ext) {
                // Usamos título como clave funcional para evitar duplicados al sincronizar.
                $pelicula = Pelicula::where('titulo', $ext['title'])->first();

                if (! $pelicula) {
                    $pelicula = new Pelicula();
                    $pelicula->titulo = $ext['title'];
                    $created++;
                } else {
                    $updated++;
                }

                $yearLabel = $ext['year'] ? ' [Year '.$ext['year'].']' : '';
                $desc = trim((string) ($ext['description'] ?? ''));
                $pelicula->sinopsis = $desc.$yearLabel;
                $pelicula->duracion_min = $pelicula->duracion_min ?: 120;
                $pelicula->classificacio_edad = $pelicula->classificacio_edad ?: 'TP';
                $pelicula->poster_path = $ext['image_url'] ?: $pelicula->poster_path;
                $pelicula->save();

                if (! empty($ext['genre'])) {
                    // Creamos categoría si no existe y la asociamos sin pisar relaciones previas.
                    $categoria = Categoria::firstOrCreate([
                        'nombre' => $ext['genre'],
                    ]);

                    $pelicula->categorias()->syncWithoutDetaching([$categoria->id]);
                }
            }
        });

        return redirect()->route('peliculas.index')->with(
            'success',
            "Local catalog updated from TMDB. New: {$created}, updated: {$updated}."
        );
    }

    /**
     * Panel de administración de películas en formato tabla.
     */
    public function adminIndex()
    {
        $peliculas = Pelicula::with('categorias')
            ->withCount('sesiones')
            ->orderByDesc('id')
            ->get();

        return view('peliculas.admin-index', compact('peliculas'));
    }

    /**
     * Muestra el formulario para crear una nueva película.
     */
    public function create()
    {
        $categorias = Categoria::all();
        return view('peliculas.create', compact('categorias'));
    }

    /**
     * Guarda una nueva película en la base de datos.
     * Acepta tanto imagen subida como URL directa de TMDB (poster_url).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo'             => 'required|string|max:255',
            'sinopsis'           => 'nullable|string',
            'duracion_min'       => 'required|integer|min:1',
            'classificacio_edad' => 'nullable|string|max:10',
            'trailer_url'        => 'nullable|url|max:255',
            'poster'             => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:4096',
            'poster_url'         => 'nullable|url|max:2048',
            'categorias'         => 'nullable|array',
            'categorias.*'       => 'exists:categorias,id',
        ]);

        // Sacamos los campos extra antes de pasar el array al modelo
        $categorias = $validated['categorias'] ?? [];
        unset($validated['categorias'], $validated['poster_url']);

        if ($request->hasFile('poster')) {
            // Archivo subido manualmente: generamos UUID y lo movemos a /public/uploads
            $validated['poster_path'] = $this->storePoster($request);
        } elseif ($request->filled('poster_url')) {
            // URL de TMDB seleccionada desde el widget de búsqueda
            $validated['poster_path'] = $request->input('poster_url');
        }

        // Creamos la película sin las categorías (son una tabla pivote aparte)
        $pelicula = Pelicula::create($validated);

        // Si el usuario marcó categorías, las enlazamos con la película
        if (! empty($categorias)) {
            $pelicula->categorias()->attach($categorias);
        }

        return redirect()->route('peliculas.index')->with('success', 'Movie created successfully.');
    }

    /**
     * AJAX — Busca películas en TMDB por texto libre.
     * Solo accesible para administradores. Usado por el widget del formulario.
     */
    public function tmdbSearch(Request $request, DevsApiHubMovieService $moviesApi): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json($moviesApi->searchByQuery($q));
    }

    /**
     * AJAX — Devuelve el detalle completo de una película TMDB por su ID.
     * El widget lo usa para auto-rellenar todos los campos del formulario.
     */
    public function tmdbDetail(int $id, DevsApiHubMovieService $moviesApi): JsonResponse
    {
        $movie = $moviesApi->getById($id);

        if (! $movie) {
            return response()->json(null, 404);
        }

        return response()->json($movie);
    }

    /**
     * Muestra el detalle de una película con sus sesiones futuras.
     */
    public function show(string $id, Request $request)
    {
        $cineId = $request->query('cine_id');
        $pelicula = Pelicula::with(['categorias', 'sesiones' => function ($q) use ($cineId) {
            $q->with('sala.cine')
              ->where('fecha_hora', '>=', now())
              ->orderBy('fecha_hora');
            if ($cineId) {
                $q->whereHas('sala', fn($s) => $s->where('fk_cine_id', $cineId));
            }
        }])->findOrFail($id);
        return view('peliculas.show', compact('pelicula', 'cineId'));
    }

    /**
     * Muestra el formulario para editar una película existente.
     */
    public function edit(string $id)
    {
        $pelicula = Pelicula::with('categorias')->findOrFail($id);
        $categorias = Categoria::all();
        return view('peliculas.edit', compact('pelicula', 'categorias'));
    }

    /**
     * Actualiza los datos de una película en la base de datos.
     */
    public function update(Request $request, string $id)
    {
        $pelicula = Pelicula::findOrFail($id);

        $validated = $request->validate([
            'titulo'             => 'required|string|max:255',
            'sinopsis'           => 'nullable|string',
            'duracion_min'       => 'required|integer|min:1',
            'classificacio_edad' => 'nullable|string|max:10',
            'trailer_url'        => 'nullable|url|max:255',
            'poster'             => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:4096',
            'categorias'         => 'nullable|array',
            'categorias.*'       => 'exists:categorias,id',
        ]);

        // Sacamos las categorías del array validado antes de actualizar
        $categorias = $validated['categorias'] ?? [];
        unset($validated['categorias']);

        if ($request->hasFile('poster')) {
            $this->deletePoster($pelicula->poster_path);
            $validated['poster_path'] = $this->storePoster($request);
        }

        // Actualizamos los campos de la película
        $pelicula->update($validated);

        // sync() borra las categorías viejas y pone las nuevas de golpe
        $pelicula->categorias()->sync($categorias);

        return redirect()->route('peliculas.index')->with('success', 'Movie updated successfully.');
    }

    /**
     * Elimina una película de la base de datos.
     */
    public function destroy(string $id)
    {
        $pelicula = Pelicula::findOrFail($id);
        $this->deletePoster($pelicula->poster_path);
        $pelicula->delete();

        return redirect()->route('peliculas.index')->with('success', 'Movie deleted successfully.');
    }

    private function storePoster(Request $request): string
    {
        $poster = $request->file('poster');
        $directory = public_path('uploads/peliculas');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = Str::uuid()->toString().'.'.$poster->getClientOriginalExtension();
        $poster->move($directory, $filename);

        return 'uploads/peliculas/'.$filename;
    }

    private function deletePoster(?string $posterPath): void
    {
        if (! $posterPath) {
            return;
        }

        $absolutePath = public_path($posterPath);
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    private function buildExternalPayload(array $validated): array
    {
        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'year' => isset($validated['year']) ? (int) $validated['year'] : null,
            'image_url' => $validated['image_url'] ?? '',
            'genre' => $validated['genre'] ?? '',
            'stars' => isset($validated['stars']) ? (float) $validated['stars'] : 0,
        ];
    }
}
