@extends('layout')
@section('title', 'Tu entrada')
@section('content')
@php
    $sesion = $reserva->sesion;
    $butacasOrdenadas = collect($butaques)->sortBy(fn ($b) => sprintf('%s%03d', preg_replace('/\d+/', '', $b), (int) preg_replace('/\D+/', '', $b)))->join(', ');
@endphp
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 md:py-14">
    <div class="mb-8 flex items-start gap-3">
        <x-icon name="check-circle" class="mt-1 h-7 w-7 shrink-0 text-ok" />
        <div>
            <h1 class="display text-4xl text-texto">Compra hecha</h1>
            <p class="mt-2 text-texto-2">Gracias, {{ $nom }}. Te hemos enviado la entrada a <strong class="text-texto">{{ $email }}</strong>.</p>
        </div>
    </div>

    {{-- La entrada: datos a la izquierda y QR a la derecha, separados por un corte perforado --}}
    <article class="grid overflow-hidden rounded-card border border-sala-700 bg-sala-900 sm:grid-cols-[1fr,auto]" aria-labelledby="entrada-titulo">
        <div class="p-6">
            <p class="cifras text-sm text-texto-2">Entrada n.º {{ $reserva->id }}</p>
            <h2 id="entrada-titulo" class="display mt-1 text-3xl text-texto">{{ $sesion->pelicula->titulo }}</h2>
            <dl class="mt-5 grid grid-cols-[auto,1fr] gap-x-6 gap-y-2 text-sm">
                <dt class="text-texto-2">Cine</dt><dd class="text-texto">{{ $sesion->sala->cine->nombre }}</dd>
                <dt class="text-texto-2">Sala</dt><dd class="text-texto">{{ $sesion->sala->nombre }}</dd>
                <dt class="text-texto-2">Día</dt><dd class="text-texto">{{ ucfirst($sesion->fecha_hora->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}</dd>
                <dt class="text-texto-2">Hora</dt><dd class="cifras text-texto">{{ $sesion->fecha_hora->format('G:i') }}</dd>
                <dt class="text-texto-2">Butacas</dt><dd class="cifras text-lg font-semibold text-marquesina">{{ $butacasOrdenadas }}</dd>
                <dt class="text-texto-2">Total</dt><dd class="cifras text-texto">{{ number_format($total, 2, ',', '.') }} €</dd>
            </dl>
        </div>
        <div class="flex flex-col items-center justify-center gap-2 border-t border-dashed border-sala-700 p-6 sm:border-l sm:border-t-0">
            <div class="rounded-control bg-white p-2">
                <img src="{{ $qrImageUrl }}" alt="Código QR de tu entrada" width="176" height="176" class="block h-44 w-44" style="image-rendering: pixelated;">
            </div>
            <p class="cifras text-sm text-texto-2">Reserva {{ $reserva->id }}</p>
        </div>
    </article>

    <p class="mt-4 text-sm text-texto-2">Enséñalo en la taquilla desde el móvil o impreso. Si no ves el correo, mira en la carpeta de spam.</p>

    <div class="mt-8 flex flex-wrap gap-3">
        @auth
            <a href="{{ route('reservas.mis') }}" class="btn btn-primary"><x-icon name="ticket" class="h-4 w-4" />Mis entradas</a>
        @endauth
        <a href="{{ route('peliculas.index') }}" class="btn btn-secondary">Volver a la cartelera</a>
    </div>
</div>
@endsection
