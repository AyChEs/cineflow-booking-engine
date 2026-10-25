@extends('layout')

@section('title', 'Gestionar películas')

@section('content')
<div class="container">
    <div class="flex-between mb-2">
        <h1 class="page-title">Películas</h1>
        <a href="{{ route('peliculas.create') }}" class="btn btn-primary">+ Nueva película</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table-premium">
        <thead>
            <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Duración</th>
                <th>Valoración</th>
                <th>Genres</th>
                <th>Screenings</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($peliculas as $pelicula)
            <tr>
                <td>{{ $pelicula->id }}</td>
                <td>{{ $pelicula->titulo }}</td>
                <td>{{ $pelicula->duracion_min ?? '—' }} min</td>
                <td>{{ $pelicula->classificacio_edad ?? 'TP' }}</td>
                <td>
                    @if($pelicula->categorias->isNotEmpty())
                        {{ $pelicula->categorias->pluck('nombre')->join(', ') }}
                    @else
                        —
                    @endif
                </td>
                <td>{{ $pelicula->sesiones_count }}</td>
                <td class="table-actions">
                    <a href="{{ route('peliculas.show', $pelicula->id) }}" class="table-link">Ver</a>
                    <a href="{{ route('peliculas.edit', $pelicula->id) }}" class="table-link" style="color: var(--color-accent-bright);">Editar</a>
                    <form action="{{ route('peliculas.destroy', $pelicula->id) }}" method="POST" style="display:inline" data-confirmar="¿Borrar esta película?">
                        @csrf @method('DELETE')
                        <button type="submit" class="table-link" style="background:none;border:none;cursor:pointer;color:#FF7A85;">Borrar</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">No movies registered yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
