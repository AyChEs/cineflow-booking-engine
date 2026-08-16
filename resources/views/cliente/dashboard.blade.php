@extends('layout')

@section('title', 'Customer Dashboard')

@section('content')
<div class="container">

    <div class="flex-between mb-2">
        <h1 class="page-title">WELCOME, {{ strtoupper(Auth::user()->name) }}</h1>
    </div>

    @if (session('status'))
        <div class="alert alert-success mb-2">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error mb-2">{{ session('error') }}</div>
    @endif

    {{-- Summary cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">

        {{-- Card: Total bookings --}}
        <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 1.5rem; text-align: center;">
            <p style="color: var(--color-text-secondary); font-size: 0.8rem; letter-spacing: 1px; margin: 0 0 0.5rem;">TOTAL BOOKINGS</p>
            <p style="font-size: 2.5rem; font-weight: 700; color: var(--color-accent-bright); margin: 0 0 1rem;">{{ $total }}</p>
            <a href="{{ route('reservas.mis') }}" class="btn btn-primary" style="width: 100%;">View Bookings</a>
        </div>

        {{-- Card: New booking --}}
        <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 1.5rem; text-align: center;">
            <p style="color: var(--color-text-secondary); font-size: 0.8rem; letter-spacing: 1px; margin: 0 0 0.5rem;">NEW BOOKING</p>
            <p style="font-size: 2.5rem; font-weight: 700; color: #27ae60; margin: 0 0 1rem;">+</p>
            <a href="{{ route('peliculas.index') }}" class="btn btn-secondary" style="width: 100%;">Make a Booking</a>
        </div>

        {{-- Card: Account info --}}
        <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 1.5rem;">
            <p style="color: var(--color-text-secondary); font-size: 0.8rem; letter-spacing: 1px; margin: 0 0 1rem;">YOUR ACCOUNT</p>
            <p style="margin: 0.3rem 0; font-size: 0.9rem;"><strong>Name:</strong> {{ Auth::user()->name }} {{ Auth::user()->apellidos }}</p>
            <p style="margin: 0.3rem 0; font-size: 0.9rem;"><strong>Email:</strong> {{ Auth::user()->email }}</p>
            <p style="margin: 0.3rem 0; font-size: 0.9rem;"><strong>Phone:</strong> {{ Auth::user()->telefono ?? '—' }}</p>
            <p style="margin: 0.5rem 0 0; font-size: 0.85rem;">
                <span style="background: var(--color-accent-bright)22; color: var(--color-accent-bright); border: 1px solid var(--color-accent-bright)44; padding: 2px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; letter-spacing: 1px;">{{ strtoupper(Auth::user()->rol) }}</span>
            </p>
        </div>
    </div>

    {{-- Recent bookings --}}
    <h2 style="font-size: 1.1rem; letter-spacing: 2px; color: var(--color-text-secondary); margin-bottom: 1rem;">RECENT BOOKINGS</h2>

    @if($reservas->isEmpty())
        <div class="alert" style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle);">
            <p style="margin: 0; color: var(--color-text-secondary);">
                You don't have any bookings yet. <a href="{{ route('peliculas.index') }}" class="table-link">Create your first one</a>
            </p>
        </div>
    @else
        <table class="table-premium">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Movie</th>
                    <th>Showtime</th>
                    <th>Seats</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reservas as $reserva)
                <tr>
                    <td>#{{ $reserva->id }}</td>
                    <td>{{ $reserva->sesion->pelicula->titulo ?? '—' }}</td>
                    <td style="color: var(--color-text-secondary); font-size: 0.85rem;">
                        {{ $reserva->sesion?->fecha_hora?->format('d/m/Y H:i') ?? '—' }}
                    </td>
                    <td style="font-family: monospace;">{{ $reserva->butaques_seleccionades }}</td>
                    <td style="color: var(--color-accent-bright); font-weight: 700;">€{{ number_format($reserva->total_pagat, 2) }}</td>
                    <td>
                        @php
                            $c = match($reserva->estat) {
                                'pagat'   => '#27ae60',
                                'pendent' => '#f39c12',
                                'cancelat'=> '#e74c3c',
                                default   => '#9ca3af',
                            };
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

        <p style="margin-top: 1rem; text-align: right;">
            <a href="{{ route('reservas.mis') }}" class="table-link">View all bookings →</a>
        </p>
    @endif

    {{-- Sign out --}}
    <div style="margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-subtle);">
        <form action="{{ route('logout') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn" style="background: #e74c3c22; color: #e74c3c; border: 1px solid #e74c3c44;">Sign Out</button>
        </form>
    </div>

</div>
@endsection

