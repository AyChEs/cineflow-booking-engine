@extends('layout')

@section('title', 'Reserva '.$reserva->id)

@section('content')
@php $sesion = $reserva->sesion; @endphp
<div class="mx-auto max-w-3xl px-4 py-pagina sm:px-6">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="cifras text-sm text-texto-2">Reserva {{ $reserva->id }}</p>
            <h1 class="display text-4xl text-texto">{{ $sesion->pelicula->titulo ?? 'Sesión borrada' }}</h1>
        </div>
        <x-estado-reserva :estat="$reserva->estat" />
    </div>

    <div class="grid gap-4 sm:grid-cols-[1fr,auto]">
        <section class="card" aria-labelledby="detalle">
            <h2 id="detalle" class="sr-only">Detalle</h2>
            <dl class="grid grid-cols-[auto,1fr] gap-x-6 gap-y-2 text-sm">
                @if ($sesion)
                    <dt class="text-texto-2">Sala</dt><dd class="text-texto">{{ $sesion->sala->nombre }}</dd>
                    <dt class="text-texto-2">Día y hora</dt><dd class="cifras text-texto">{{ ucfirst($sesion->fecha_hora->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}, {{ $sesion->fecha_hora->format('G:i') }}</dd>
                @endif
                <dt class="text-texto-2">Butacas</dt><dd class="cifras font-semibold text-marquesina">{{ $reserva->butaques_seleccionades ?: 'Sin butacas' }}</dd>
                <dt class="text-texto-2">Total</dt><dd class="cifras text-texto">{{ number_format($reserva->total_pagat, 2, ',', '.') }} €</dd>
                @if ($reserva->usuario && auth()->user()->canManage())
                    <dt class="text-texto-2">Cliente</dt><dd class="text-texto">{{ $reserva->usuario->name }} {{ $reserva->usuario->apellidos }} · {{ $reserva->usuario->email }}</dd>
                @endif
                <dt class="text-texto-2">Comprada</dt><dd class="cifras text-texto">{{ $reserva->created_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY, H:mm') }}</dd>
            </dl>
        </section>

        @if ($qrImageUrl)
            <section class="card flex flex-col items-center gap-2" aria-labelledby="qr">
                <h2 id="qr" class="text-sm font-semibold text-texto-2">Tu entrada</h2>
                <div class="rounded-control bg-white p-2">
                    <img src="{{ $qrImageUrl }}" alt="Código QR de tu entrada" width="176" height="176" class="block h-44 w-44" style="image-rendering: pixelated;">
                </div>
                <p class="max-w-[12rem] text-center text-sm text-texto-2">Enséñalo en la taquilla.</p>
            </section>
        @endif
    </div>

    <div class="mt-8 flex flex-wrap gap-3">
        @if (auth()->user()->canManage())
            <a href="{{ route('reservas.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" />Todas las reservas</a>
        @else
            <a href="{{ route('reservas.mis') }}" class="btn btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" />Mis entradas</a>
        @endif
        @if (auth()->user()->isAdmin())
            <a href="{{ route('reservas.edit', $reserva->id) }}" class="btn btn-secondary"><x-icon name="pencil" class="h-4 w-4" />Editar</a>
            <form action="{{ route('reservas.destroy', $reserva->id) }}" method="POST" data-confirmar="¿Borrar esta reserva?">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger"><x-icon name="trash" class="h-4 w-4" />Borrar</button>
            </form>
        @endif
    </div>
</div>
@endsection
