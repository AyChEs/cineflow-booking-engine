@extends('layout')

@section('content')
@php
    $tituloDestacada = $destacada?->titulo ?? ($tmdbHero['title'] ?? null);
    $sinopsis = $destacada?->sinopsis ?? ($tmdbHero['description'] ?? null);
    $poster = $destacada?->poster_url ?? ($tmdbHero['image_url'] ?? null);
    $enlace = $destacada ? route('peliculas.show', $destacada) : route('peliculas.index');
@endphp

@unless ($tituloDestacada)
    <h1 class="sr-only">CineFlow, cartelera y entradas</h1>
@endunless

@if ($tituloDestacada)
<section class="relative isolate overflow-hidden border-b border-sala-700" aria-labelledby="destacada">
    @if ($poster)
        {{-- El póster, difuminado y oscurecido, hace de luz de sala detrás del titular. --}}
        <div class="absolute inset-0 -z-10" aria-hidden="true">
            <img src="{{ $poster }}" alt="" class="h-full w-full scale-110 object-cover opacity-30 blur-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-sala-950 via-sala-950/80 to-sala-950/40"></div>
            <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-sala-950"></div>
        </div>
    @endif

    <div class="mx-auto grid max-w-7xl items-center gap-8 px-4 py-10 sm:px-6 md:grid-cols-[1fr,auto] md:gap-12 md:py-20">
        @if ($poster)
            <img src="{{ $poster }}" alt="Póster de {{ $tituloDestacada }}" width="300" height="450"
                 fetchpriority="high" decoding="async"
                 class="order-first mx-auto aspect-[2/3] w-44 rounded-card border border-sala-700 object-cover sm:w-56 md:order-last md:w-72">
        @endif

        <div class="max-w-2xl">
            <p class="mb-3 text-sm font-semibold text-carmin-400">En cartelera</p>
            <h1 id="destacada" class="display text-[2.5rem] text-texto md:text-[4rem]">{{ $tituloDestacada }}</h1>

            @if ($destacada)
                <p class="cifras mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-texto-2">
                    @if ($destacada->duracion_min)<span>{{ $destacada->duracion_min }} min</span>@endif
                    @foreach ($destacada->categorias->take(3) as $categoria)
                        <span class="rounded-full border border-sala-700 px-2 text-xs">{{ $categoria->nombre }}</span>
                    @endforeach
                </p>
            @endif

            @if ($sinopsis)
                <p class="mt-5 line-clamp-3 max-w-prose text-base text-texto-2 md:text-lg">{{ $sinopsis }}</p>
            @endif

            <div class="mt-7 flex flex-wrap gap-3">
                <a href="{{ $enlace }}" class="btn btn-primary btn-lg">
                    <x-icon name="ticket" class="h-5 w-5" />Ver sesiones
                </a>
                @if ($destacada?->trailer_url)
                    <a href="{{ $destacada->trailer_url }}" target="_blank" rel="noopener" class="btn btn-secondary btn-lg">
                        <x-icon name="play" class="h-5 w-5" />Ver tráiler<span class="sr-only"> (se abre en otra pestaña)</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
@endif

<section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 md:py-20" aria-labelledby="en-cartelera">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <h2 id="en-cartelera" class="display text-3xl text-texto md:text-4xl">En cartelera</h2>
        <a href="{{ route('peliculas.index') }}" class="btn btn-secondary btn-sm">
            Toda la cartelera<x-icon name="arrow-right" class="h-4 w-4" />
        </a>
    </div>

    @if ($peliculas->isEmpty())
        <p class="text-texto-2">Ahora mismo no hay películas en cartelera.</p>
    @else
        <ul class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4 lg:gap-x-6">
            @foreach ($peliculas as $pelicula)
                <li><x-movie-card :pelicula="$pelicula" /></li>
            @endforeach
        </ul>
    @endif
</section>

@if ($cines->isNotEmpty())
<section class="border-t border-sala-700" aria-labelledby="nuestros-cines">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 md:py-20">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <h2 id="nuestros-cines" class="display text-3xl text-texto md:text-4xl">Cines</h2>
            <a href="{{ route('cines.index') }}" class="btn btn-secondary btn-sm">
                Todos los cines<x-icon name="arrow-right" class="h-4 w-4" />
            </a>
        </div>
        <ul class="divide-y divide-sala-700 rounded-card border border-sala-700 bg-sala-900">
            @foreach ($cines as $cine)
                <li class="relative flex flex-wrap items-center justify-between gap-2 px-4 py-4 hover:bg-sala-850 sm:px-6">
                    <div>
                        <h3 class="font-semibold text-texto">
                            <a href="{{ route('cines.show', $cine) }}" class="no-underline after:absolute after:inset-0 after:content-['']">{{ $cine->nombre }}</a>
                        </h3>
                        <p class="flex items-center gap-1.5 text-sm text-texto-2">
                            <x-icon name="map-pin" class="h-4 w-4 shrink-0" />{{ $cine->ciudad }}
                        </p>
                    </div>
                    <p class="cifras text-sm text-texto-2">{{ $cine->salas_count }} {{ $cine->salas_count === 1 ? 'sala' : 'salas' }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
@endsection
