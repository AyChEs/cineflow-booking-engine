@extends('layout')

@section('title', 'Screening Room Details')

@section('content')
<div class="container" style="max-width: 700px;">
    <div class="flex-between mb-2">
        <h1 class="page-title">{{ $sala->nombre }}</h1>
        <div style="display: flex; gap: 1rem; align-items: center;">
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('salas.edit', $sala->id) }}" class="btn btn-primary" style="font-size: 0.875rem;">Edit</a>
                @endif
            @endauth
            <a href="{{ route('salas.index') }}" class="btn btn-secondary" style="font-size: 0.875rem;">← Back</a>
        </div>
    </div>

    <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem; margin-bottom: 1.5rem;">
        <h2 style="color: var(--color-text-secondary); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.25rem;">Screening Room Information</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">ID</p>
                <p style="font-weight: 600;">#{{ $sala->id }}</p>
            </div>
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Capacity</p>
                <p style="font-weight: 600;">{{ $sala->capacidad }} people</p>
            </div>
            @if($sala->cine)
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Cinema</p>
                <p style="font-weight: 600;">
                    <a href="{{ route('cines.show', $sala->cine->id) }}" class="table-link">{{ $sala->cine->nombre }}</a>
                </p>
            </div>
            @endif
        </div>
        <div>
            <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.5rem;">Seat Layout</p>
            <div style="background: var(--color-bg-primary); border: 1px solid var(--border-subtle); border-radius: 8px; padding: 1rem; font-family: monospace; white-space: pre-wrap; color: var(--color-text-primary);">{{ $sala->disposicion_butacas }}</div>
        </div>
    </div>
</div>
@endsection
