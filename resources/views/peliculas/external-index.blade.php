@extends('layout')

@section('title', 'Catálogo de TMDB')

@section('content')
<div class="container py-10 max-w-7xl">

    {{-- Header --}}
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title mb-1">En cartelera según TMDB</h1>
            <p class="text-[color:var(--color-text-secondary)] text-sm flex items-center gap-2">
                <x-icon name="satellite" class="h-3 w-3 text-carmin-400" />
                Películas de <code class="bg-sala-900 px-2 py-0.5 rounded text-xs">api.themoviedb.org/3</code>
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            @auth
                @if(auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('admin.peliculas.external.sync-local') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <x-icon name="refresh" class="h-4 w-4 mr-1" /> Actualizar la cartelera local
                        </button>
                    </form>
                @endif
            @endauth
            <a href="{{ route('peliculas.index') }}" class="btn btn-secondary btn-sm">
                <x-icon name="arrow-left" class="h-4 w-4 mr-1" /> Cartelera local
            </a>
        </div>
    </div>

    {{-- Session alerts --}}
    @if(session('success'))
        <div class="alert alert-success flex items-center gap-3 mb-6">
            <x-icon name="check-circle" class="h-5 w-5 flex-shrink-0" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error flex items-center gap-3 mb-6">
            <x-icon name="alert" class="h-5 w-5 flex-shrink-0" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Aviso API desactivada --}}
    @if(! $apiEnabled)
        <div class="alert alert-warning flex items-start gap-3 mb-6">
            <x-icon name="warning" class="h-6 w-6 flex-shrink-0 mt-0.5" />
            <div>
                <p class="font-bold text-sm mb-1">TMDB no está configurado</p>
                <p class="text-sm opacity-80">
                    Enable <code class="bg-black/20 px-1.5 py-0.5 rounded">MOVIES_API_ENABLED=true</code>
                    and set <code class="bg-black/20 px-1.5 py-0.5 rounded">TMDB_API_KEY</code> in your <code class="bg-black/20 px-1.5 py-0.5 rounded">.env</code>.
                </p>
            </div>
        </div>
    @endif

    {{-- Info readonly --}}
    <div class="flex items-center gap-3 mb-6 px-4 py-3 rounded-xl border border-black/10 bg-black/[0.03] text-[color:var(--color-text-secondary)] text-sm">
        <x-icon name="info" class="h-4 w-4 text-carmin-400 flex-shrink-0" />
        <span>This TMDB integration is <strong class="text-carmin-400">read-only</strong>. To update your local listings, use the <strong class="text-carmin-400">Actualizar la cartelera local</strong> button.</span>
    </div>

    {{-- Filter form: parameters are sent as a query string so filtered URLs can be shared --}}
    <form method="GET" action="{{ route('peliculas.external.index') }}"
          class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-5 gap-3 mb-8 items-end">

        <div>
            <label for="genre" class="block text-xs font-bold uppercase tracking-widest text-[color:var(--color-text-secondary)] mb-1.5">Genre</label>
            <input id="genre" name="genre" value="{{ request('genre') }}" placeholder="Acción, Drama…"
                   class="form-input text-sm py-2">
        </div>

        <div>
            <label for="year" class="block text-xs font-bold uppercase tracking-widest text-[color:var(--color-text-secondary)] mb-1.5">Year</label>
            <input id="year" name="year" type="number" min="1900" max="2100" value="{{ request('year') }}"
                   class="form-input text-sm py-2">
        </div>

        <div>
            <label for="stars" class="block text-xs font-bold uppercase tracking-widest text-[color:var(--color-text-secondary)] mb-1.5">
                <x-icon name="star" class="h-4 w-4 text-marquesina mr-0.5" /> Valoración (0 a 5)
            </label>
            <input id="stars" name="stars" type="number" min="0" max="5" step="0.1" value="{{ request('stars') }}"
                   class="form-input text-sm py-2">
        </div>

        <div>
            <label for="limit" class="block text-xs font-bold uppercase tracking-widest text-[color:var(--color-text-secondary)] mb-1.5">Limit</label>
            <input id="limit" name="limit" type="number" min="1" max="100" value="{{ request('limit') }}"
                   placeholder="20"
                   class="form-input text-sm py-2">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-1">
                <x-icon name="search" class="h-4 w-4 mr-1" /> Filter
            </button>
            <a href="{{ route('peliculas.external.index') }}" class="btn btn-secondary btn-sm px-3" title="Quitar filtros">
                <x-icon name="close" class="h-4 w-4" />
            </a>
        </div>
    </form>

    {{-- Count of results returned by TMDB after applying the current filter --}}
    <p class="text-[color:var(--color-text-secondary)] text-sm mb-5">
        <span class="text-carmin-400 font-black">{{ count($movies) }}</span> result{{ count($movies) !== 1 ? 's' : '' }}
        @if(request()->hasAny(['genre','year','stars','limit']))
            <span class="text-carmin-400"> · filtered</span>
        @endif
    </p>

    {{-- Grid of movies normalized by the Service (title, year, stars, image_url, genre) --}}
    <div class="grid gap-5 reveal-stagger" style="grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));">
        @forelse($movies as $movie)
            <div class="card-movie group">
                <div class="card-movie-poster">
                    <img src="{{ $movie['image_url'] ?: asset('img/sin-poster.svg') }}"
                         alt="Poster for {{ $movie['title'] }}" loading="lazy">
                    <div class="card-movie-overlay">
                        <div class="text-center px-4 w-full">
                            <a href="{{ route('peliculas.external.show', $movie['id']) }}"
                               class="btn btn-primary btn-sm" style="width:100%;display:block;">
                                Ver detalle
                            </a>
                        </div>
                    </div>
                    <div class="card-movie-badge">{{ $movie['genre'] ?: 'No genre' }}</div>
                </div>
                <div class="card-movie-body">
                    <div class="card-movie-title">{{ $movie['title'] }}</div>
                    <div class="card-movie-meta">
                        <span class="cm-rating"><x-icon name="star" class="h-4 w-4" />{{ number_format((float) ($movie['stars'] ?? 0), 1) }}</span>
                        <span class="cm-sep">·</span>
                        <span>{{ $movie['year'] ?: 'N/A' }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-20 text-[color:var(--color-text-secondary)]">
                <x-icon name="film" class="h-12 w-12 opacity-20 block mb-4" />
                <p class="text-lg font-semibold mb-2">No results</p>
                <p class="text-sm mb-5">No hay películas con esos filtros en TMDB.</p>
                <a href="{{ route('peliculas.external.index') }}" class="btn btn-secondary btn-sm">
                    Ver todas las películas
                </a>
            </div>
        @endforelse
    </div>

</div>
@endsection
