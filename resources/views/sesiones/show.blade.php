@extends('layout')

@section('title', 'Showtime Details')

@section('content')
<div class="container" style="max-width: 900px;">
    <div class="flex-between mb-2">
        <h1 class="page-title">SHOWTIME #{{ $sesion->id }}</h1>
        <div style="display: flex; gap: 1rem; align-items: center;">
            @auth
                @if(auth()->check() && auth()->user()->isAdmin())
                    <a href="{{ route('sesiones.edit', $sesion->id) }}" class="btn btn-primary" style="font-size: 0.875rem;">Edit</a>
                    <form method="POST" action="{{ route('sesiones.destroy', $sesion->id) }}" style="display: inline;"
                          onsubmit="return confirm('Delete this showtime and its bookings?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-secondary" style="font-size: 0.875rem; color: #e74c3c; border-color: #e74c3c;">Delete</button>
                    </form>
                @endif
            @endauth
            <a href="{{ route('sesiones.index') }}" class="btn btn-secondary" style="font-size: 0.875rem;">← Back</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-2">{{ session('success') }}</div>
    @endif

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem;">
            <h2 style="color: var(--color-text-secondary); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.25rem;">Showtime Information</h2>
            <div style="margin-bottom: 1rem;">
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Date and Time</p>
                <p style="font-weight: 600;">{{ $sesion->fecha_hora->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Base Price</p>
                <p style="font-weight: 600; color: var(--color-accent-bright);">{{ number_format($sesion->preu_base, 2) }}€</p>
            </div>
        </div>

        <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem;">
            <h2 style="color: var(--color-text-secondary); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.25rem;">Details</h2>
            <div style="margin-bottom: 1rem;">
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Created</p>
                <p style="font-weight: 600;">{{ $sesion->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Last Updated</p>
                <p style="font-weight: 600;">{{ $sesion->updated_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem;">
            <h2 style="color: var(--color-text-secondary); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.25rem;">Movie</h2>
            @if($sesion->pelicula)
                <p style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem;">{{ $sesion->pelicula->titulo }}</p>
                <p style="color: var(--color-text-secondary); font-size: 0.85rem; margin-bottom: 0.5rem;">{{ $sesion->pelicula->duracion_min }} minutes</p>
                @if($sesion->pelicula->classificacio_edad)
                    <span style="background: var(--color-accent-dark); color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">{{ $sesion->pelicula->classificacio_edad }}</span>
                @endif
                <p style="margin-top: 1rem;">
                    <a href="{{ route('peliculas.show', $sesion->pelicula->id) }}" class="table-link">View details →</a>
                </p>
            @else
                <p style="color: var(--color-text-secondary); font-style: italic;">Movie not available</p>
            @endif
        </div>

        <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem;">
            <h2 style="color: var(--color-text-secondary); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.25rem;">Screening Room</h2>
            @if($sesion->sala)
                <p style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem;">{{ $sesion->sala->nombre }}</p>
                <p style="color: var(--color-text-secondary); font-size: 0.85rem; margin-bottom: 0.5rem;">{{ $sesion->sala->capacidad }} people</p>
                <p style="margin-top: 1rem;">
                    <a href="{{ route('salas.show', $sesion->sala->id) }}" class="table-link">View details →</a>
                </p>
            @else
                <p style="color: var(--color-text-secondary); font-style: italic;">Screening room not available</p>
            @endif
        </div>
    </div>

    @if($sesion->reservas && $sesion->reservas->count() > 0)
    <h2 class="page-title" style="font-size: 1rem; margin-bottom: 1rem;">Bookings ({{ $sesion->reservas->count() }})</h2>
    <table class="table-premium">
        <thead><tr>
            <th>ID</th>
            <th>Status</th>
            <th>Total</th>
            <th class="text-center">Actions</th>
        </tr></thead>
        <tbody>
            @foreach($sesion->reservas as $reserva)
            <tr>
                <td>#{{ $reserva->id }}</td>
                <td>{{ ucfirst($reserva->estat) }}</td>
                <td>{{ number_format($reserva->total_pagat, 2) }}€</td>
                <td class="table-actions">
                    <a href="{{ route('reservas.show', $reserva->id) }}" class="table-link">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="alert" style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle);">
        <p style="margin: 0; color: var(--color-text-secondary);">This showtime has no bookings yet.</p>
    </div>
    @endif
</div>
@endsection
