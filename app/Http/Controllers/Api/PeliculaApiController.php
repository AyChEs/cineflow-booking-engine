<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Cine;
use App\Models\Pelicula;
use App\Models\Sesion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeliculaApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Pelicula::query()
            ->with([
                'categorias:id,nombre',
                'sesiones' => function ($query) {
                    $query->with(['sala.cine'])
                        ->where('fecha_hora', '>=', now())
                        ->orderBy('fecha_hora');
                },
            ])
            ->withCount([
                'sesiones as sesiones_disponibles_count' => fn($query) => $query->where('fecha_hora', '>=', now()),
            ]);

        if ($request->filled('dia')) {
            $query->whereHas('sesiones', fn($sessionQuery) =>
                $sessionQuery->whereDate('fecha_hora', $request->string('dia')->toString())
            );
        }

        if ($request->filled('cine_id')) {
            $query->whereHas('sesiones.sala', fn($roomQuery) =>
                $roomQuery->where('fk_cine_id', $request->integer('cine_id'))
            );
        }

        if ($request->filled('categoria_id')) {
            $query->whereHas('categorias', fn($categoryQuery) =>
                $categoryQuery->where('categorias.id', $request->integer('categoria_id'))
            );
        }

        $horaDesde = (int) $request->input('hora_desde', 0);
        $horaHasta = (int) $request->input('hora_hasta', 23);

        if ($request->has('hora_desde') || $request->has('hora_hasta')) {
            if ($horaDesde > 0 || $horaHasta < 23) {
                $query->whereHas('sesiones', fn($sessionQuery) =>
                    $sessionQuery->whereTime('fecha_hora', '>=', sprintf('%02d:00', $horaDesde))
                        ->whereTime('fecha_hora', '<=', sprintf('%02d:59', $horaHasta))
                );
            }
        }

        $peliculas = $query->orderByDesc('id')->get();

        return response()->json([
            'data' => $peliculas->map(fn(Pelicula $pelicula) => $this->transformPelicula($pelicula, true)),
            'meta' => [
                'total' => $peliculas->count(),
                'filtros' => array_filter([
                    'dia' => $request->input('dia'),
                    'cine_id' => $request->input('cine_id'),
                    'categoria_id' => $request->input('categoria_id'),
                    'hora_desde' => $request->input('hora_desde'),
                    'hora_hasta' => $request->input('hora_hasta'),
                ], fn($value) => $value !== null && $value !== ''),
            ],
        ]);
    }

    public function show(string $id, Request $request): JsonResponse
    {
        $cineId = $request->integer('cine_id');

        $pelicula = Pelicula::with([
            'categorias:id,nombre',
            'sesiones' => function ($query) use ($cineId) {
                $query->with(['sala.cine'])
                    ->where('fecha_hora', '>=', now())
                    ->orderBy('fecha_hora');

                if ($cineId > 0) {
                    $query->whereHas('sala', fn($roomQuery) => $roomQuery->where('fk_cine_id', $cineId));
                }
            },
        ])->findOrFail($id);

        return response()->json([
            'data' => $this->transformPelicula($pelicula, true),
        ]);
    }

    public function cines(): JsonResponse
    {
        $cines = Cine::withCount('salas')
            ->orderBy('nombre')
            ->get()
            ->map(fn(Cine $cine) => [
                'id' => $cine->id,
                'nombre' => $cine->nombre,
                'ciudad' => $cine->ciudad,
                'provincia' => $cine->provincia,
                'direccion_completa' => $cine->direccion_completa,
                'salas_count' => $cine->salas_count,
                'url' => route('cines.show', $cine),
            ]);

        return response()->json([
            'data' => $cines,
        ]);
    }

    public function categorias(): JsonResponse
    {
        $categorias = Categoria::withCount('peliculas')
            ->orderBy('nombre')
            ->get()
            ->map(fn(Categoria $categoria) => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'peliculas_count' => $categoria->peliculas_count,
            ]);

        return response()->json([
            'data' => $categorias,
        ]);
    }

    public function filtros(): JsonResponse
    {
        $dias = Sesion::query()
            ->where('fecha_hora', '>=', now())
            ->selectRaw('DATE(fecha_hora) as dia')
            ->distinct()
            ->orderBy('dia')
            ->pluck('dia');

        return response()->json([
            'data' => [
                'dias' => $dias,
                'cines' => Cine::withCount('salas')->orderBy('nombre')->get(['id', 'nombre', 'ciudad']),
                'categorias' => Categoria::withCount('peliculas')->orderBy('nombre')->get(['id', 'nombre']),
            ],
        ]);
    }

    private function transformPelicula(Pelicula $pelicula, bool $includeSessions): array
    {
        $payload = [
            'id' => $pelicula->id,
            'titulo' => $pelicula->titulo,
            'sinopsis' => $pelicula->sinopsis,
            'duracion_min' => $pelicula->duracion_min,
            'classificacio_edad' => $pelicula->classificacio_edad,
            'trailer_url' => $pelicula->trailer_url,
            'poster_path' => $pelicula->poster_path,
            'poster_url' => $pelicula->poster_url,
            'categorias' => $pelicula->categorias->pluck('nombre')->values(),
            'sesiones_disponibles_count' => $pelicula->sesiones_disponibles_count ?? $pelicula->sesiones->count(),
            'url' => route('peliculas.show', $pelicula),
        ];

        if (! $includeSessions) {
            return $payload;
        }

        $payload['sesiones'] = $pelicula->sesiones->map(function ($sesion) {
            return [
                'id' => $sesion->id,
                'fecha_hora' => optional($sesion->fecha_hora)?->toIso8601String(),
                'fecha_hora_formateada' => optional($sesion->fecha_hora)?->format('d/m/Y H:i'),
                'preu_base' => $sesion->preu_base,
                'sala' => [
                    'id' => $sesion->sala?->id,
                    'nombre' => $sesion->sala?->nombre,
                ],
                'cine' => [
                    'id' => $sesion->sala?->cine?->id,
                    'nombre' => $sesion->sala?->cine?->nombre,
                    'ciudad' => $sesion->sala?->cine?->ciudad,
                ],
            ];
        })->values();

        return $payload;
    }
}