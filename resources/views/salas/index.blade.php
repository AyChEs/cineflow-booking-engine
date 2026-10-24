@extends('layout')

@section('title', 'Salas')

@section('content')
<div class="container">
    <div class="flex-between mb-2">
        <h1 class="page-title">Salas</h1>
        <a href="{{ route('salas.create') }}" class="btn btn-primary">+ Nueva sala</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table-premium">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Cine</th>
                <th>Capacidad</th>
                <th>Disposición</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salas as $sala)
            <tr>
                <td>{{ $sala->id }}</td>
                <td>{{ $sala->nombre }}</td>
                <td>{{ $sala->cine?->nombre ?? '—' }}</td>
                <td>{{ $sala->capacidad }}</td>
                <td>{{ $sala->disposicion_butacas }}</td>
                <td class="table-actions">
                    <a href="{{ route('salas.show', $sala) }}" class="table-link">Ver</a>
                    <a href="{{ route('salas.edit', $sala) }}" class="table-link" style="color: var(--color-accent-bright);">Editar</a>
                    <form action="{{ route('salas.destroy', $sala) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Borrar esta sala?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="table-link" style="background:none;border:none;cursor:pointer;color:#FF7A85;">Borrar</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">No screening rooms registered.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection