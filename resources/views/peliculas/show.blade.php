@extends('layout')

@section('mov', 'sitio')

@section('title', $pelicula->titulo)

@section('content')
@php
    $hoy = now()->toDateString();
    $manana = now()->addDay()->toDateString();
    $porDia = $pelicula->sesiones->groupBy(fn ($s) => $s->fecha_hora->toDateString());
    $nombreDia = fn (string $dia) => match ($dia) {
        $hoy => 'Hoy',
        $manana => 'Mañana',
        default => ucfirst(\Carbon\Carbon::parse($dia)->locale('es')->isoFormat('dddd D [de] MMMM')),
    };
@endphp
{{-- El fondo de la película, oscurecido y teñido de rojo telón, vuelve a la ficha el tono de la portada --}}
<div class="ficha-fondo" aria-hidden="true">
    @if ($pelicula->tiene_fondo)
        <img src="{{ $pelicula->fondo_url }}" alt="" width="1280" height="720" decoding="async" onerror="this.remove()">
    @else
        {{-- Sin fondo propio: la sala de la marca, en lugar del póster ampliado --}}
        <x-marca.imagen nombre="sala" sizes="100vw" />
    @endif
    <x-marca.imagen nombre="haz" sizes="(min-width: 1024px) 70vw, 100vw" class="ficha-haz" data-haz />
</div>
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 md:py-12">

    <a href="{{ route('peliculas.index') }}{{ $cineId ? '?cine_id='.$cineId : '' }}"
       class="mb-8 inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-texto-2 no-underline hover:text-texto">
        <x-icon name="arrow-left" class="h-4 w-4" />Volver a la cartelera
    </a>

    <div class="grid items-start gap-8 md:grid-cols-[18rem,1fr] md:gap-12">
        <img data-respaldo="{{ $pelicula->cartel_respaldo_url }}" onerror="this.onerror=null;this.src=this.dataset.respaldo"
             src="{{ $pelicula->poster_url }}" alt="Póster de {{ $pelicula->titulo }}" width="300" height="450"
             fetchpriority="high" decoding="async"
             class="mx-auto aspect-[2/3] w-48 rounded-card border border-sala-700 bg-sala-850 object-cover sm:w-60 md:w-full" style="view-transition-name: poster-{{ $pelicula->id }}">

        <div class="min-w-0">
            <h1 class="display break-words text-[2.25rem] text-texto md:text-[3rem]">{{ $pelicula->titulo }}</h1>

            <dl class="mt-6 grid grid-cols-[auto,1fr] gap-x-6 gap-y-2 text-sm">
                @if ($pelicula->duracion_min)
                    <dt class="text-texto-2">Duración</dt>
                    <dd class="cifras text-texto">{{ $pelicula->duracion_min }} min</dd>
                @endif
                @if ($pelicula->classificacio_edad)
                    <dt class="text-texto-2">Clasificación</dt>
                    <dd class="text-texto">{{ $pelicula->classificacio_edad === 'TP' ? 'Todos los públicos' : $pelicula->classificacio_edad }}</dd>
                @endif
                @if ($pelicula->categorias->isNotEmpty())
                    <dt class="text-texto-2">Géneros</dt>
                    <dd class="text-texto">{{ $pelicula->categorias->pluck('nombre')->join(', ') }}</dd>
                @endif
                @if ($pelicula->rating)
                    <dt class="text-texto-2">Valoración</dt>
                    <dd class="cifras text-texto">{{ number_format($pelicula->rating, 1, ',', '') }} / 5</dd>
                @endif
            </dl>

            @if ($pelicula->sinopsis)
                <p class="mt-6 max-w-prose text-base leading-relaxed text-texto-2">{{ $pelicula->sinopsis }}</p>
            @endif

            <div class="mt-6 flex flex-wrap gap-3">
                @if ($pelicula->trailer_url)
                    <a href="{{ $pelicula->trailer_url }}" target="_blank" rel="noopener" class="btn btn-secondary">
                        <x-icon name="play" class="h-4 w-4" />Ver tráiler<span class="sr-only"> (se abre en otra pestaña)</span>
                    </a>
                @endif
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('peliculas.edit', $pelicula->id) }}" class="btn btn-secondary">
                            <x-icon name="pencil" class="h-4 w-4" />Editar
                        </a>
                        <form action="{{ route('peliculas.destroy', $pelicula->id) }}" method="POST"
                              onsubmit="return confirm('¿Borrar esta película y sus sesiones?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger"><x-icon name="trash" class="h-4 w-4" />Borrar</button>
                        </form>
                    @endif
                @endauth
            </div>
        </div>
    </div>

    <section class="mt-14" aria-labelledby="sesiones">
        <h2 id="sesiones" class="display mb-6 text-3xl text-texto">Sesiones</h2>

        @forelse ($porDia as $dia => $sesionesDia)
            <section class="mb-8" aria-labelledby="dia-{{ $dia }}">
                <h3 id="dia-{{ $dia }}" class="mb-4 text-lg font-semibold text-texto">{{ $nombreDia($dia) }}</h3>
                @foreach ($sesionesDia->groupBy(fn ($s) => $s->sala->cine->nombre) as $nombreCine => $sesionesCine)
                    <div class="mb-4">
                        <p class="mb-2 flex items-center gap-1.5 text-sm text-texto-2">
                            <x-icon name="map-pin" class="h-4 w-4 shrink-0" />{{ $nombreCine }}
                        </p>
                        <ul class="flex flex-wrap gap-3">
                            @foreach ($sesionesCine as $sesion)
                                <li><x-showtime :sesion="$sesion" /></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </section>
        @empty
            <div class="rounded-card border border-sala-700 bg-sala-900 px-6 py-10 text-center">
                <p class="font-semibold text-texto">No hay sesiones programadas</p>
                <a href="{{ route('peliculas.index') }}" class="btn btn-secondary mt-5">Ver la cartelera</a>
            </div>
        @endforelse
    </section>
</div>
@endsection
