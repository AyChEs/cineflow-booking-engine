@extends('layout')

@section('mov', 'portada')

@section('content')
@php
    $tituloDestacada = $destacada?->titulo ?? ($tmdbHero['title'] ?? null);
    $sinopsis = $destacada?->sinopsis ?? ($tmdbHero['description'] ?? null);
    $fondo = $destacada?->fondo_url ?? ($tmdbHero['image_url'] ?? null);
    // Sin fondo propio (ni backdrop de TMDB ni fondo del cartel sustituto) el hero usa la sala de la marca, no el póster ampliado.
    $usaSala = $tituloDestacada && ($destacada ? ! $destacada->tiene_fondo : ! $fondo);
    $enlace = $destacada ? route('peliculas.show', $destacada) : route('peliculas.index');
    $resto = $destacada ? $peliculas->reject(fn ($p) => $p->id === $destacada->id)->values() : $peliculas;
@endphp

@if ($usaSala)
    @push('precarga')
        <link rel="preload" as="image" type="image/avif" imagesrcset="{{ collect(config('marca.fotos.sala.anchos'))->map(fn ($w) => asset("img/marca/fotos/sala-{$w}.avif")." {$w}w")->join(', ') }}" imagesizes="100vw" fetchpriority="high">
    @endpush
@elseif ($fondo)
    @push('precarga')
        <link rel="preconnect" href="https://image.tmdb.org">
        <link rel="preload" as="image" href="{{ $fondo }}" @if ($destacada?->fondo_srcset) imagesrcset="{{ $destacada->fondo_srcset }}" imagesizes="100vw" @endif fetchpriority="high">
    @endpush
@endif

@unless ($tituloDestacada)
    <h1 class="sr-only">{{ config('app.name') }}, cartelera y entradas</h1>
@endunless

{{-- ── Portada y cartelera: en escritorio, una sola escena fijada (el hero se encoge hasta ser el primer fotograma de la tira) ── --}}
<section class="escena" aria-label="Portada y cartelera">
    <div class="escena-pista" data-escena>
        <div class="film" data-film>
            @if ($tituloDestacada)
            <article class="frame-hero" data-hero aria-labelledby="destacada">
                @if ($usaSala)
                    <div class="hero-media hero-media--sala" data-hero-media aria-hidden="true">
                        <x-marca.imagen nombre="sala" sizes="100vw" :prioridad="true" />
                    </div>
                @elseif ($fondo)
                    <div class="hero-media" data-hero-media aria-hidden="true">
                        <img src="{{ $fondo }}" @if ($destacada?->fondo_srcset) srcset="{{ $destacada->fondo_srcset }}" sizes="100vw" @endif alt="" width="1280" height="720" fetchpriority="high" decoding="async">
                    </div>
                @endif
                <div class="hero-tinte" aria-hidden="true"></div>
                <div class="hero-rojo" aria-hidden="true"></div>
                <div class="hero-haz" aria-hidden="true">
                    <x-marca.imagen nombre="haz" sizes="100vw" class="hero-haz-img" data-haz />
                </div>
                <div class="hero-grano" aria-hidden="true"></div>

                <div class="hero-contenido" data-hero-contenido>
                    <div class="hero-bombillas" aria-hidden="true"></div>
                    <p class="hero-etiqueta" data-hero-detalle>En cartelera</p>
                    <h1 id="destacada" class="hero-titulo" data-titulo>{{ $tituloDestacada }}</h1>

                    @if ($destacada)
                        <p class="hero-meta cifras" data-hero-detalle>
                            @if ($destacada->duracion_min)<span>{{ $destacada->duracion_min }} min</span>@endif
                            @foreach ($destacada->categorias->take(3) as $categoria)
                                <span class="hero-genero">{{ $categoria->nombre }}</span>
                            @endforeach
                        </p>
                    @endif
                    @if ($sinopsis)
                        <p class="hero-sinopsis" data-hero-detalle>{{ $sinopsis }}</p>
                    @endif

                    <div class="hero-acciones" data-hero-detalle>
                        <a href="{{ $enlace }}" class="btn btn-primary btn-lg btn-brillo" data-magnetico>
                            <x-icon name="ticket" class="h-5 w-5" />Ver sesiones
                        </a>
                        @if ($destacada?->trailer_url)
                            <a href="{{ $destacada->trailer_url }}" target="_blank" rel="noopener" class="btn btn-secondary btn-lg hero-trailer">
                                <x-icon name="play" class="h-5 w-5" />Ver tráiler<span class="sr-only"> (se abre en otra pestaña)</span>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Una vez encogido, todo el fotograma lleva a la ficha --}}
                <a href="{{ $enlace }}" class="hero-fotograma-enlace" data-hero-enlace tabindex="-1" aria-hidden="true"></a>

                <div class="hero-banda hero-banda--sup" aria-hidden="true"></div>
                <div class="hero-banda hero-banda--inf" aria-hidden="true"></div>
            </article>
            @endif

            <div class="film-banda" data-film-banda aria-hidden="true"></div>

            <div class="tira" data-tira>
                @if ($resto->isEmpty() && ! $tituloDestacada)
                    <p class="px-4 text-texto-2">Ahora mismo no hay películas en cartelera.</p>
                @else
                    <ul class="tira-lista" aria-labelledby="en-cartelera">
                        @foreach ($resto as $pelicula)
                            <li><x-movie-card :pelicula="$pelicula" variante="tira" nivel="h2" /></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="tira-cabecera" data-tira-cabecera>
            <h2 id="en-cartelera" class="display text-4xl text-texto md:text-5xl">En cartelera</h2>
            <a href="{{ route('peliculas.index') }}" class="btn btn-secondary btn-sm" data-magnetico>
                Toda la cartelera<x-icon name="arrow-right" class="h-4 w-4" />
            </a>
        </div>
        <div class="film-progreso" data-film-progreso aria-hidden="true"><i></i></div>
    </div>
</section>

@include('portada._compra')

{{-- ── El nombre, a todo el ancho: se rellena con el scroll y los pósteres lo cruzan ── --}}
<section class="nombre-gigante" data-nombre aria-labelledby="lema">
    <div class="nombre-fondo" aria-hidden="true"><x-marca.imagen nombre="sala" sizes="100vw" /></div>
    <h2 id="lema" class="nombre-lema display">{{ config('marca.lema') }}</h2>
    <div class="nombre-lienzo" aria-hidden="true">
        <x-marca.nombre-ancho class="nombre-trazo-capa" />
        <div class="nombre-ventana" data-nombre-ventana>
            <x-marca.nombre-ancho :relleno="true" class="nombre-relleno-capa" data-nombre-relleno />
        </div>
        @foreach ($resto->take(3) as $i => $pelicula)
            <img src="{{ str_replace('/t/p/w500/', '/t/p/w342/', $pelicula->poster_url) }}" alt="" width="300" height="450" loading="lazy" fetchpriority="low" decoding="async"
                 class="cruce cruce--{{ $i + 1 }}" data-cruce="{{ $i + 1 }}"
                 data-respaldo="{{ $pelicula->cartel_respaldo_url }}">
        @endforeach
    </div>
</section>

@if ($cines->isNotEmpty())
<section class="mx-auto w-full max-w-7xl px-4 pb-pagina pt-seccion sm:px-6" aria-labelledby="nuestros-cines">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <h2 id="nuestros-cines" class="display text-4xl text-texto md:text-5xl">Cines</h2>
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
</section>
@endif
@endsection
