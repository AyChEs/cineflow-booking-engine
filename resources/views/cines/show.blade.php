@extends('layout')

@section('title', $cine->nombre)

@section('content')
@php
    // Sesiones futuras de este cine, agrupadas por película (solo lectura; no cambia el modelo).
    $sesiones = \App\Models\Sesion::with('pelicula.categorias', 'sala')
        ->whereIn('fk_sala_id', $cine->salas->pluck('id'))
        ->where('fecha_hora', '>=', now())
        ->orderBy('fecha_hora')
        ->get();
    $hoy = now()->toDateString();
    $manana = now()->addDay()->toDateString();
    $nombreDia = fn (string $dia) => match ($dia) {
        $hoy => 'Hoy',
        $manana => 'Mañana',
        default => ucfirst(\Carbon\Carbon::parse($dia)->locale('es')->isoFormat('dddd D [de] MMMM')),
    };
@endphp
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 md:py-12">

    <a href="{{ route('cines.index') }}" class="mb-8 inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-texto-2 no-underline hover:text-texto">
        <x-icon name="arrow-left" class="h-4 w-4" />Todos los cines
    </a>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="display text-4xl text-texto md:text-5xl">{{ $cine->nombre }}</h1>
            <p class="mt-3 flex items-start gap-1.5 text-texto-2">
                <x-icon name="map-pin" class="mt-1 h-4 w-4 shrink-0" />
                <span>{{ $cine->direccion_completa }}, {{ $cine->ciudad }}@if ($cine->provincia && $cine->provincia !== $cine->ciudad) ({{ $cine->provincia }})@endif</span>
            </p>
            <p class="cifras mt-1 text-sm text-texto-2">
                {{ $cine->salas->count() }} {{ $cine->salas->count() === 1 ? 'sala' : 'salas' }}:
                {{ $cine->salas->map(fn ($s) => "{$s->nombre} ({$s->capacidad} butacas)")->join(', ') }}
            </p>
        </div>
        @auth
            @if (auth()->user()->isAdmin())
                <a href="{{ route('cines.edit', $cine->id) }}" class="btn btn-secondary btn-sm"><x-icon name="pencil" class="h-4 w-4" />Editar</a>
            @endif
        @endauth
    </div>

    <section class="mt-12" aria-labelledby="en-este-cine">
        <h2 id="en-este-cine" class="display mb-6 text-3xl text-texto">En este cine</h2>

        @forelse ($sesiones->groupBy('fk_pelicula_id') as $sesionesPeli)
            @php $pelicula = $sesionesPeli->first()->pelicula; @endphp
            <article class="mb-6 grid gap-5 rounded-card border border-sala-700 bg-sala-900 p-4 sm:grid-cols-[7rem,1fr] sm:p-6">
                <img src="{{ $pelicula->poster_url }}" alt="Póster de {{ $pelicula->titulo }}" width="300" height="450"
                     loading="lazy" decoding="async"
                     class="hidden aspect-[2/3] w-28 rounded-control border border-sala-700 object-cover sm:block">
                <div class="min-w-0">
                    <h3 class="text-xl font-semibold text-texto">
                        <a href="{{ route('peliculas.show', ['id' => $pelicula->id, 'cine_id' => $cine->id]) }}" class="hover:text-carmin-400">{{ $pelicula->titulo }}</a>
                    </h3>
                    <p class="cifras mb-4 text-sm text-texto-2">
                        @if ($pelicula->duracion_min){{ $pelicula->duracion_min }} min · @endif{{ $pelicula->categorias->pluck('nombre')->take(3)->join(', ') }}
                    </p>
                    @foreach ($sesionesPeli->groupBy(fn ($s) => $s->fecha_hora->toDateString())->take(3) as $dia => $delDia)
                        <h4 class="mb-2 text-sm font-semibold text-texto">{{ $nombreDia($dia) }}</h4>
                        <ul class="mb-4 flex flex-wrap gap-2">
                            @foreach ($delDia as $sesion)
                                <li><x-showtime :sesion="$sesion" /></li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </article>
        @empty
            <div class="rounded-card border border-sala-700 bg-sala-900 px-6 py-10 text-center">
                <p class="font-semibold text-texto">No hay sesiones programadas en este cine</p>
                <a href="{{ route('peliculas.index') }}" class="btn btn-secondary mt-5">Ver la cartelera</a>
            </div>
        @endforelse
    </section>
</div>
@endsection
