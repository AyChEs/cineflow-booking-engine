@extends('layout')

@section('title', 'Booking Details')

@section('content')
<div class="container" style="max-width: 700px;">
    <div class="flex-between mb-2">
        <h1 class="page-title">BOOKING #{{ $reserva->id }}</h1>
        <div style="display: flex; gap: 1rem; align-items: center;">
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('reservas.edit', $reserva->id) }}" class="btn btn-primary" style="font-size: 0.875rem;">Edit</a>
                    <form action="{{ route('reservas.destroy', $reserva->id) }}" method="POST" style="display: inline;"
                          onsubmit="return confirm('Delete this booking?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-secondary" style="font-size: 0.875rem; color: #e74c3c; border-color: #e74c3c;">Delete</button>
                    </form>
                @endif
            @endauth
            <a href="{{ route('reservas.index') }}" class="btn btn-secondary" style="font-size: 0.875rem;">← Back</a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem;">
            <h2 style="color: var(--color-text-secondary); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.25rem;">User</h2>
            <p style="font-weight: 700; margin-bottom: 0.5rem;">{{ $reserva->usuario->name }} {{ $reserva->usuario->apellidos }}</p>
            <p style="color: var(--color-text-secondary); font-size: 0.85rem; margin-bottom: 0.25rem;">{{ $reserva->usuario->email }}</p>
            <p style="color: var(--color-text-secondary); font-size: 0.85rem;">{{ $reserva->usuario->telefono ?? '-' }}</p>
        </div>

        <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem;">
            <h2 style="color: var(--color-text-secondary); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.25rem;">Summary</h2>
            <div style="margin-bottom: 0.75rem;">
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.15rem;">Total Paid</p>
                <p style="font-weight: 700; font-size: 1.25rem; color: var(--color-accent-bright);">€{{ number_format($reserva->total_pagat, 2) }}</p>
            </div>
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Status</p>
                @php
                    $estatColor = match($reserva->estat) {
                        'pagat'    => '#27ae60',
                        'pendent'  => '#f39c12',
                        'cancelat' => '#e74c3c',
                        default    => '#9ca3af',
                    };
                @endphp
                <span style="background: {{ $estatColor }}22; color: {{ $estatColor }}; border: 1px solid {{ $estatColor }}44; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 0.85rem;">
                    {{ ucfirst($reserva->estat) }}
                </span>
            </div>
        </div>
    </div>

    <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem; margin-bottom: 1.5rem;">
        <h2 style="color: var(--color-text-secondary); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.25rem;">Showtime</h2>
        @if($reserva->sesion)
            <p style="font-weight: 600; margin-bottom: 0.5rem;">
                #{{ $reserva->sesion->id }} – {{ $reserva->sesion->pelicula->titulo }}
            </p>
            <p style="color: var(--color-text-secondary); font-size: 0.85rem; margin-bottom: 0.25rem;">
                Screening Room: {{ $reserva->sesion->sala->nombre }}
            </p>
            <p style="color: var(--color-text-secondary); font-size: 0.85rem;">
                Date: {{ $reserva->sesion->fecha_hora->format('d/m/Y H:i') }}
            </p>
        @else
            <p style="color: var(--color-text-secondary); font-style: italic;">Showtime deleted</p>
        @endif
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-subtle);">
            <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Seats</p>
            <p style="font-weight: 600; font-family: monospace;">{{ $reserva->butaques_seleccionades }}</p>
        </div>
    </div>

    @if($qrImageUrl)
    <div style="background: var(--color-bg-surface); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem; margin-bottom: 1.5rem; text-align:center;">
        <h2 style="color: var(--color-text-secondary); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.25rem;">Ticket · QR Code</h2>
        <div style="width:200px;margin:0 auto;background:#fff;border:1px solid var(--border-subtle);border-radius:12px;padding:0.75rem;">
            <img src="{{ $qrImageUrl }}" alt="QR code for booking #{{ $reserva->id }}" style="width:100%;height:auto;display:block;image-rendering:pixelated;">
        </div>
        <p style="color: var(--color-text-secondary); font-size: 0.78rem; margin-top: 0.85rem;">Show this QR code at the box office to access the screening room.</p>
    </div>
    @endif

    <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 1.5rem;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Created</p>
                <p style="font-weight: 600; font-size: 0.9rem;">{{ $reserva->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-bottom: 0.25rem;">Last Updated</p>
                <p style="font-weight: 600; font-size: 0.9rem;">{{ $reserva->updated_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
