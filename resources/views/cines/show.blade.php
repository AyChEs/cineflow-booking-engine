@extends('layout')

@section('title', 'Cinema Details')

@section('content')
<div class="container" style="max-width: 900px;">
    <div class="flex-between mb-2">
        <h1 class="page-title">{{ $cine->nombre }}</h1>
        <div style="display: flex; gap: 1rem; align-items: center;">
            @auth
                @if(auth()->check() && auth()->user()->isAdmin())
                    <a href="{{ route('cines.edit', $cine->id) }}" class="btn btn-primary" style="font-size: 0.875rem;">Edit</a>
                @endif
            @endauth
            <a href="{{ route('cines.index') }}" class="btn btn-secondary" style="font-size: 0.875rem;">← Back</a>
        </div>
    </div>

    <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem; margin-bottom: 1.5rem;">
        <h2 style="color: var(--color-text-secondary); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.25rem;">Cinema Information</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Address</p>
                <p style="font-weight: 600;">{{ $cine->direccion_completa }}</p>
            </div>
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">City</p>
                <p style="font-weight: 600;">{{ $cine->ciudad }}</p>
            </div>
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Province</p>
                <p style="font-weight: 600;">{{ $cine->provincia }}</p>
            </div>
        </div>
    </div>

    <h2 class="page-title" style="font-size: 1.1rem; margin-bottom: 1rem;">Screens at this cinema</h2>

    @if($cine->salas->isEmpty())
        <div class="alert" style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle);">
            <p style="margin: 0; color: var(--color-text-secondary);">This cinema has no screens assigned yet.</p>
        </div>
    @else
        <table class="table-premium">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Capacity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cine->salas as $sala)
                    <tr>
                        <td>{{ $sala->nombre }}</td>
                        <td>{{ $sala->capacidad }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
