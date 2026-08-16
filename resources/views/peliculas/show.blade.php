@extends('layout')

@section('title', $pelicula->titulo)

@section('content')
<div class="container max-w-5xl py-10">

    {{-- Back --}}
    <a href="{{ route('peliculas.index') }}{{ $cineId ? '?cine_id='.$cineId : '' }}"
       class="inline-flex items-center gap-2 text-[color:var(--color-text-secondary)] hover:text-[color:var(--color-accent-bright)] text-sm font-semibold transition-colors mb-8">
        <i class="fas fa-arrow-left text-xs"></i> Back to Now Showing
    </a>

    {{-- Capçalera: poster + info --}}
    <div class="grid gap-8 items-start mb-10" style="grid-template-columns: 200px 1fr;">

        {{-- Poster --}}
        <div class="aspect-[2/3] bg-gradient-to-br from-cinema-surface to-black rounded-xl overflow-hidden border border-white/10 shadow-2xl">
            <img src="{{ $pelicula->poster_url }}"
                 alt="Poster for {{ $pelicula->titulo }}"
                 class="w-full h-full object-cover block">
        </div>

        {{-- Info --}}
        <div>
            <h1 class="text-4xl font-black leading-tight mb-3">{{ $pelicula->titulo }}</h1>

            {{-- Badges: rating + categories + duration --}}
            <div class="flex flex-wrap gap-2 mb-4">
                @if($pelicula->classificacio_edad)
                    <span class="px-3 py-1 rounded text-xs font-black uppercase tracking-wider
                                 bg-cinema-red text-white">
                        {{ $pelicula->classificacio_edad }}
                    </span>
                @endif
                @foreach($pelicula->categorias as $cat)
                    <span class="px-3 py-1 rounded text-xs font-semibold
                                 bg-cinema-surface text-cinema-muted border border-white/10">
                        {{ $cat->nombre }}
                    </span>
                @endforeach
                @if($pelicula->duracion_min)
                    <span class="px-3 py-1 rounded text-xs font-semibold
                                 bg-cinema-surface text-cinema-muted border border-white/10">
                        <i class="fas fa-clock mr-1 opacity-60"></i>{{ $pelicula->duracion_min }} min
                    </span>
                @endif
            </div>

            @if($pelicula->sinopsis)
                <p class="text-[color:var(--color-text-secondary)] leading-relaxed text-sm max-w-xl mb-4">
                    {{ $pelicula->sinopsis }}
                </p>
            @endif

            @if($pelicula->trailer_url)
                <a href="{{ $pelicula->trailer_url }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 text-cinema-accent font-bold text-sm hover:opacity-80 transition-opacity mb-5">
                    <i class="fas fa-play-circle"></i> Watch Trailer
                </a>
            @endif

            {{-- Acciones admin --}}
            @auth @if(auth()->user()->isAdmin())
                <div class="flex gap-3 mt-6">
                    <a href="{{ route('peliculas.edit', $pelicula->id) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                    <form action="{{ route('peliculas.destroy', $pelicula->id) }}" method="POST"
                          onsubmit="return confirm('Delete this movie and its screenings?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="btn btn-secondary btn-sm border-red-500/50 text-red-400 hover:bg-red-500/10">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </button>
                    </form>
                </div>
            @endif @endauth
        </div>
    </div>

    {{-- Sessions disponibles --}}
    <div>
        <h2 class="text-xs font-black uppercase tracking-[0.2em] text-[color:var(--color-text-secondary)] mb-5">
            <i class="fas fa-calendar-alt mr-2 text-cinema-accent/60"></i>Available Screenings
        </h2>

        @php
            $today    = now()->toDateString();
            $tomorrow = now()->addDay()->toDateString();
        @endphp

        @forelse($pelicula->sesiones->groupBy(fn($s) => $s->fecha_hora->format('Y-m-d')) as $dia => $sessions)
            <div class="mb-8">

                {{-- Day label with relative indicator (Today / Tomorrow / day name) --}}
                <h3 class="flex items-center gap-2.5 mb-4">
                    @if($dia === $today)
                        <span class="bg-cinema-accent text-white text-[0.6rem] font-black px-2.5 py-1
                                     rounded uppercase tracking-widest">
                            Today
                        </span>
                    @elseif($dia === $tomorrow)
                        <span class="bg-white/10 text-white text-[0.6rem] font-black px-2.5 py-1
                                     rounded uppercase tracking-widest border border-white/20">
                            Tomorrow
                        </span>
                    @else
                        <span class="text-xs font-bold uppercase tracking-widest text-[color:var(--color-text-secondary)]">
                            {{ \Carbon\Carbon::parse($dia)->locale('en')->isoFormat('dddd, MMMM D') }}
                        </span>
                    @endif
                </h3>

                {{-- Showtime cards: large time + highlighted price + buy button --}}
                <div class="flex flex-wrap gap-3">
                    @foreach($sessions as $sesion)
                        <div class="bg-cinema-surface border border-white/10 rounded-xl p-4 min-w-[200px]
                                    hover:border-cinema-accent/50 hover:bg-white/[0.03] transition-all duration-200">

                            {{-- Hora grande en acento rojo --}}
                            <div class="text-2xl font-black text-cinema-accent leading-none mb-1">
                                {{ $sesion->fecha_hora->format('H:i') }}
                            </div>

                            <div class="text-xs text-cinema-muted mb-3 leading-snug">
                                {{ $sesion->sala->cine->nombre }}
                                <span class="opacity-30 mx-1">–</span>
                                {{ $sesion->sala->nombre }}
                            </div>

                            {{-- Precio prominente --}}
                            <div class="flex items-end justify-between mb-3">
                                <span class="text-xl font-black text-white leading-none">
                                    {{ number_format($sesion->preu_base, 2) }}€
                                </span>
                                <span class="text-[0.6rem] text-cinema-muted opacity-60 mb-0.5">
                                    / ticket
                                </span>
                            </div>

                            <a href="{{ route('compra.step1', ['sesion_id' => $sesion->id]) }}"
                               class="btn btn-primary btn-sm block text-center">
                                <i class="fas fa-ticket-alt mr-1.5"></i>Buy Tickets
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-cinema-surface border border-white/10 rounded-xl p-8 text-center text-cinema-muted">
                <i class="fas fa-calendar-times text-3xl opacity-20 block mb-3"></i>
                This movie has no screenings scheduled.
            </div>
        @endforelse
    </div>

</div>
@endsection
