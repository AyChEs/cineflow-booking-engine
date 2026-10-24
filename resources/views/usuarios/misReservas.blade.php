@extends('layout')

@section('title', 'Mis entradas')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 md:py-14">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <h1 class="display text-4xl text-texto">Mis entradas</h1>
        <a href="{{ route('peliculas.index') }}" class="btn btn-primary"><x-icon name="ticket" class="h-4 w-4" />Comprar entradas</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status"><x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" />{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error" role="alert"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />{{ session('error') }}</div>
    @endif

    @if ($reservas->isEmpty())
        <div class="rounded-card border border-sala-700 bg-sala-900 px-6 py-12 text-center">
            <p class="font-semibold text-texto">Todavía no tienes entradas</p>
            <a href="{{ route('peliculas.index') }}" class="btn btn-secondary mt-5">Ver la cartelera</a>
        </div>
    @else
        <ul class="flex flex-col gap-3">
            @foreach ($reservas as $reserva)
                <li class="relative grid gap-3 rounded-card border border-sala-700 bg-sala-900 p-4 sm:grid-cols-[1fr,auto] sm:items-center sm:p-5">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-texto">
                            <a href="{{ route('reservas.show', $reserva) }}" class="hover:text-carmin-400">{{ $reserva->sesion->pelicula->titulo ?? 'Sesión borrada' }}</a>
                        </h2>
                        <p class="cifras mt-1 text-sm text-texto-2">
                            @if ($reserva->sesion)
                                {{ ucfirst($reserva->sesion->fecha_hora->locale('es')->isoFormat('dddd D [de] MMMM')) }}, {{ $reserva->sesion->fecha_hora->format('G:i') }} · {{ $reserva->sesion->sala->nombre ?? '' }}
                            @endif
                        </p>
                        <p class="cifras mt-1 text-sm">
                            <span class="text-texto-2">Butacas</span> <span class="font-semibold text-marquesina">{{ $reserva->butaques_seleccionades ?: 'Sin butacas' }}</span>
                            <span class="text-texto-2"> · {{ number_format($reserva->total_pagat, 2, ',', '.') }} €</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                        <x-estado-reserva :estat="$reserva->estat" />
                        @if ($reserva->estat === 'pendent')
                            <form action="{{ route('reservas.cancelar', $reserva) }}" method="POST" onsubmit="return confirm('¿Cancelar esta reserva?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Cancelar</button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        @if ($reservas->hasPages())
            <div class="mt-8">{{ $reservas->links() }}</div>
        @endif
    @endif
</div>
@endsection
