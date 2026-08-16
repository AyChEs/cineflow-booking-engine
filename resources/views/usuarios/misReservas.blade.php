@extends('layout')

@section('title', 'My Bookings')

@section('content')
<div class="container">
    <div class="flex-between mb-2">
        <h1 class="page-title">MY BOOKINGS</h1>
        <a href="{{ route('peliculas.index') }}" class="btn btn-primary">+ New Booking</a>
    </div>

    @if(session('status'))
        <div class="alert alert-success mb-2">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error mb-2">{{ session('error') }}</div>
    @endif

    @if($reservas->isEmpty())
        <div class="alert" style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle);">
            <p style="margin: 0; color: var(--color-text-secondary);">
                You don't have any bookings yet.
                <a href="{{ route('peliculas.index') }}" class="table-link">Create a new one</a>
            </p>
        </div>
    @else
        <table class="table-premium">
            <thead><tr>
                <th>ID</th>
                <th>Showtime</th>
                <th>Seats</th>
                <th>Type</th>
                <th>Total</th>
                <th>Status</th>
                <th class="text-center">Actions</th>
            </tr></thead>
            <tbody>
                @foreach($reservas as $reserva)
                <tr>
                    <td>#{{ $reserva->id }}</td>
                    <td>
                        @if($reserva->sesion)
                            {{ $reserva->sesion->pelicula->titulo ?? '?' }}
                            <span style="color: var(--color-text-secondary); font-size: 0.8rem; display: block;">
                                {{ $reserva->sesion->fecha_hora->format('d/m/Y H:i') }}
                            </span>
                        @else
                            <em style="color: var(--color-text-secondary);">Showtime deleted</em>
                        @endif
                    </td>
                    <td style="font-family: monospace;">{{ $reserva->butaques_seleccionades }}</td>
                    <td>
                        @php
                            $tipusIcons = ['adult' => '🧑', 'infantil' => '🧒', 'jubilat' => '👴', 'discapacitat' => '♿'];
                            $tipusLabels = ['adult' => 'Adult', 'infantil' => 'Child', 'jubilat' => 'Senior', 'discapacitat' => 'Disabled'];
                            $t = $reserva->tipus_entrada ?? 'adult';
                        @endphp
                        <span style="font-size: 0.85rem;">{{ $tipusIcons[$t] ?? '' }} {{ $tipusLabels[$t] ?? ucfirst($t) }}</span>
                    </td>
                    <td style="color: var(--color-accent-bright); font-weight: 700;">€{{ number_format($reserva->total_pagat, 2) }}</td>
                    <td>
                        @php
                            $c = match($reserva->estat) { 'pagat' => '#27ae60', 'pendent' => '#f39c12', 'cancelat' => '#e74c3c', default => '#9ca3af' };
                        @endphp
                        <span style="background: {{ $c }}22; color: {{ $c }}; border: 1px solid {{ $c }}44; padding: 3px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 700;">
                            {{ ucfirst($reserva->estat) }}
                        </span>
                    </td>
                    <td class="table-actions">
                        <a href="{{ route('reservas.show', $reserva) }}" class="table-link">View</a>
                        @if($reserva->estat === 'pendent')
                            <form action="{{ route('reservas.cancelar', $reserva) }}" method="POST"
                                  style="display: inline;" onsubmit="return confirm('Cancel this booking?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="table-link" style="background: none; border: none; cursor: pointer; color: #e74c3c;">Cancel</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($reservas->hasPages())
            <div style="margin-top: 1.5rem; text-align: center;">
                {{ $reservas->links() }}
            </div>
        @endif
    @endif

    <div style="margin-top: 2rem;">
        <a href="{{ route('cliente.dashboard') }}" class="table-link">← Back to dashboard</a>
    </div>
</div>
@endsection
