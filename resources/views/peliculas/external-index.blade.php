@extends('layout')

@section('title', 'External TMDB Listings')

@section('content')
<div class="container py-10 max-w-7xl">

    {{-- Header --}}
    <div class="flex items-end justify-between mb-8">
        <div>
            <h1 class="page-title mb-1">TMDB NOW SHOWING</h1>
            <p class="text-[color:var(--color-text-secondary)] text-sm flex items-center gap-2">
                <i class="fas fa-satellite-dish text-cinema-accent text-xs"></i>
                Movies from <code class="bg-cinema-surface px-2 py-0.5 rounded text-xs">api.themoviedb.org/3</code>
            </p>
        </div>
        <div class="flex gap-3">
            @auth
                @if(auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('admin.peliculas.external.sync-local') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <i class="fas fa-sync-alt mr-1"></i> Update local listings
                        </button>
                    </form>
                @endif
            @endauth
            <a href="{{ route('peliculas.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Local listings
            </a>
        </div>
    </div>

    {{-- Session alerts --}}
    @if(session('success'))
        <div class="alert alert-success flex items-center gap-3 mb-6">
            <i class="fas fa-check-circle text-lg flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error flex items-center gap-3 mb-6">
            <i class="fas fa-exclamation-circle text-lg flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Aviso API desactivada --}}
    @if(! $apiEnabled)
        <div class="alert alert-warning flex items-start gap-3 mb-6">
            <i class="fas fa-exclamation-triangle text-xl flex-shrink-0 mt-0.5"></i>
            <div>
                <p class="font-bold text-sm mb-1">TMDB is not configured</p>
                <p class="text-sm opacity-80">
                    Enable <code class="bg-black/20 px-1.5 py-0.5 rounded">MOVIES_API_ENABLED=true</code>
                    and set <code class="bg-black/20 px-1.5 py-0.5 rounded">TMDB_API_KEY</code> in your <code class="bg-black/20 px-1.5 py-0.5 rounded">.env</code>.
                </p>
            </div>
        </div>
    @endif

    {{-- Info readonly --}}
    <div class="flex items-center gap-3 mb-6 px-4 py-3 rounded-xl border border-black/10 bg-black/[0.03] text-[color:var(--color-text-secondary)] text-sm">
        <i class="fas fa-info-circle text-cinema-accent/70 flex-shrink-0"></i>
        <span>This TMDB integration is <strong class="text-cinema-accent">read-only</strong>. To update your local listings, use the <strong class="text-cinema-accent">Update local listings</strong> button.</span>
    </div>

    {{-- Filter form: parameters are sent as a query string so filtered URLs can be shared --}}
    <form method="GET" action="{{ route('peliculas.external.index') }}"
          class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-5 gap-3 mb-8 items-end">

        <div>
            <label for="genre" class="block text-xs font-bold uppercase tracking-widest text-[color:var(--color-text-secondary)] mb-1.5">Genre</label>
            <input id="genre" name="genre" value="{{ request('genre') }}" placeholder="Action, Drama..."
                   class="form-input text-sm py-2">
        </div>

        <div>
            <label for="year" class="block text-xs font-bold uppercase tracking-widest text-[color:var(--color-text-secondary)] mb-1.5">Year</label>
            <input id="year" name="year" type="number" min="1900" max="2100" value="{{ request('year') }}"
                   class="form-input text-sm py-2">
        </div>

        <div>
            <label for="stars" class="block text-xs font-bold uppercase tracking-widest text-[color:var(--color-text-secondary)] mb-1.5">
                <i class="fas fa-star text-yellow-500 mr-0.5"></i> Rating (0-5)
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
                <i class="fas fa-search mr-1"></i> Filter
            </button>
            <a href="{{ route('peliculas.external.index') }}" class="btn btn-secondary btn-sm px-3" title="Clear filters">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>

    {{-- Count of results returned by TMDB after applying the current filter --}}
    <p class="text-[color:var(--color-text-secondary)] text-sm mb-5">
        <span class="text-cinema-accent font-black">{{ count($movies) }}</span> result{{ count($movies) !== 1 ? 's' : '' }}
        @if(request()->hasAny(['genre','year','stars','limit']))
            <span class="text-cinema-accent"> · filtered</span>
        @endif
    </p>

    {{-- Grid of movies normalized by the Service (title, year, stars, image_url, genre) --}}
    <div class="grid gap-5 reveal-stagger" style="grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));">
        @forelse($movies as $movie)
            <div class="card-movie group">
                <div class="card-movie-poster">
                    <img src="{{ $movie['image_url'] ?: 'https://placehold.co/600x900/ece7e3/9b1229?text=No+Image' }}"
                         alt="Poster for {{ $movie['title'] }}" loading="lazy">
                    <div class="card-movie-overlay">
                        <div class="text-center px-4 w-full">
                            <a href="{{ route('peliculas.external.show', $movie['id']) }}"
                               class="btn btn-primary btn-sm" style="width:100%;display:block;">
                                View Details
                            </a>
                        </div>
                    </div>
                    <div class="card-movie-badge">{{ $movie['genre'] ?: 'No genre' }}</div>
                </div>
                <div class="card-movie-body">
                    <div class="card-movie-title">{{ $movie['title'] }}</div>
                    <div class="card-movie-meta">
                        <span class="cm-rating"><i class="fas fa-star"></i>{{ number_format((float) ($movie['stars'] ?? 0), 1) }}</span>
                        <span class="cm-sep">·</span>
                        <span>{{ $movie['year'] ?: 'N/A' }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-20 text-[color:var(--color-text-secondary)]">
                <i class="fas fa-film text-5xl opacity-20 block mb-4"></i>
                <p class="text-lg font-semibold mb-2">No results</p>
                <p class="text-sm mb-5">No movies match those filters in the external API.</p>
                <a href="{{ route('peliculas.external.index') }}" class="btn btn-secondary btn-sm">
                    View all movies
                </a>
            </div>
        @endforelse
    </div>

</div>
@endsection
