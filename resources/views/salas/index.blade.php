@extends('layout')

@section('title', 'Screening Rooms')

@section('content')
<div class="container">
    <div class="flex-between mb-2">
        <h1 class="page-title">SCREENING ROOMS</h1>
        <a href="{{ route('salas.create') }}" class="btn btn-primary">+ New Screening Room</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table-premium">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Cinema</th>
                <th>Capacity</th>
                <th>Layout</th>
                <th>Actions</th>
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
                    <a href="{{ route('salas.show', $sala) }}" class="table-link">View</a>
                    <a href="{{ route('salas.edit', $sala) }}" class="table-link" style="color: var(--color-accent-bright);">Edit</a>
                    <form action="{{ route('salas.destroy', $sala) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this screening room?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="table-link" style="background:none;border:none;cursor:pointer;color:#e74c3c;">Delete</button>
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