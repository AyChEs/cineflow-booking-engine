@extends('layout')

@section('title', 'Manage Cinemas')

@section('content')
<div class="container">
    <div class="flex-between mb-2">
        <h1 class="page-title">CINEMAS</h1>
        <a href="{{ route('cines.create') }}" class="btn btn-primary">+ New Cinema</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table-premium">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>City</th>
                <th>Province</th>
                <th>Screens</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cines as $cine)
            <tr>
                <td>{{ $cine->id }}</td>
                <td>{{ $cine->nombre }}</td>
                <td>{{ $cine->ciudad }}</td>
                <td>{{ $cine->provincia }}</td>
                <td>{{ $cine->salas_count }}</td>
                <td class="table-actions">
                    <a href="{{ route('cines.show', $cine->id) }}" class="table-link">View</a>
                    <a href="{{ route('cines.edit', $cine->id) }}" class="table-link" style="color: var(--color-accent-bright);">Edit</a>
                    <form action="{{ route('cines.destroy', $cine->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this cinema?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="table-link" style="background:none;border:none;cursor:pointer;color:#e74c3c;">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">No cinemas registered yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
