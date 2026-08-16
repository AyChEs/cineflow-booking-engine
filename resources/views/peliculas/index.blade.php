@extends('layout')

@section('title', 'Now Showing')

@section('content')
<div class="container py-10 max-w-7xl">

    {{-- Single form: wraps the whole listing so search,
         sort and filters travel together on every submit. --}}
    <form method="GET" action="{{ route('peliculas.index') }}" id="filterForm">

    {{-- ================= Capçalera + toolbar ================= --}}
    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
        <div>
            <h1 class="page-title mb-1 section-title-accent is-visible">NOW SHOWING</h1>
            <p class="text-[color:var(--color-text-secondary)] text-sm">
                <span class="text-cinema-accent font-black">{{ $peliculas->count() }}</span> movies
                @if($filtered)
                    <span class="text-cinema-accent"> · filtered</span>
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- Ordre --}}
            <label class="flex items-center gap-2 text-xs">
                <span class="text-[color:var(--color-text-secondary)] uppercase tracking-widest font-black text-[0.6rem] hidden sm:inline">Sort</span>
                <select name="orden" onchange="document.getElementById('filterForm').submit()"
                        class="form-select text-sm py-1.5 w-auto">
                    <option value="rating" {{ $orden === 'rating' ? 'selected' : '' }}>Top Rated</option>
                    <option value="titulo" {{ $orden === 'titulo' ? 'selected' : '' }}>Title (A–Z)</option>
                    <option value="durada" {{ $orden === 'durada' ? 'selected' : '' }}>Duration</option>
                </select>
            </label>

            {{-- Filters button (mobile/tablet only) --}}
            <button type="button" id="filtersToggle" class="btn btn-secondary btn-sm filters-toggle">
                <i class="fas fa-sliders-h mr-1.5"></i>Filters
                @if($activeFilters > 0)
                    <span class="filter-count">{{ $activeFilters }}</span>
                @endif
            </button>

            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.peliculas.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-cog mr-1.5"></i> Manage
                    </a>
                @endif
            @endauth
        </div>
    </div>

    {{-- ================= Cercador ================= --}}
    <div class="search-cartelera mb-5">
        <i class="fas fa-search search-cartelera-icon"></i>
        <input type="search" name="q" value="{{ request('q') }}"
               placeholder="Search for a movie…"
               autocomplete="off"
               class="search-cartelera-input">
        @if(request('q'))
            <a href="{{ route('peliculas.index', request()->except('q')) }}"
               class="search-cartelera-clear" aria-label="Clear search">&times;</a>
        @endif
    </div>

    {{-- ================= Chips de filtres actius ================= --}}
    @if($filtered)
        <div class="flex flex-wrap items-center gap-2 mb-6">
            <span class="text-[color:var(--color-text-secondary)] text-xs uppercase tracking-widest font-black mr-1">Filters:</span>

            @if(request('q'))
                <a href="{{ route('peliculas.index', request()->except('q')) }}" class="filter-chip">
                    <i class="fas fa-search"></i> “{{ Str::limit(request('q'), 20) }}” <span>&times;</span>
                </a>
            @endif
            @if(request('dia'))
                <a href="{{ route('peliculas.index', request()->except('dia')) }}" class="filter-chip">
                    <i class="fas fa-calendar-day"></i>
                    {{ \Carbon\Carbon::parse(request('dia'))->locale('en')->isoFormat('ddd D MMM') }} <span>&times;</span>
                </a>
            @endif
            @if(request('cine_id'))
                @php $cineActiu = $filterCines->firstWhere('id', (int) request('cine_id')); @endphp
                <a href="{{ route('peliculas.index', request()->except('cine_id')) }}" class="filter-chip">
                    <i class="fas fa-map-marker-alt"></i> {{ $cineActiu->nombre ?? 'Cinema' }} <span>&times;</span>
                </a>
            @endif
            @if(request('categoria_id'))
                @php $catActiva = $filterCats->firstWhere('id', (int) request('categoria_id')); @endphp
                <a href="{{ route('peliculas.index', request()->except('categoria_id')) }}" class="filter-chip">
                    <i class="fas fa-tag"></i> {{ $catActiva->nombre ?? 'Genre' }} <span>&times;</span>
                </a>
            @endif
            @if(request('hora_desde', 0) > 0 || request('hora_hasta', 23) < 23)
                <a href="{{ route('peliculas.index', request()->except(['hora_desde', 'hora_hasta'])) }}" class="filter-chip">
                    <i class="fas fa-clock"></i>
                    {{ str_pad(request('hora_desde', 0), 2, '0', STR_PAD_LEFT) }}:00–{{ str_pad(request('hora_hasta', 23), 2, '0', STR_PAD_LEFT) }}:00
                    <span>&times;</span>
                </a>
            @endif

            <a href="{{ route('peliculas.index') }}" class="filter-chip filter-chip-clear">
                Clear all
            </a>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success flex items-center gap-3 mb-6">
            <i class="fas fa-check-circle flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ================= Layout: sidebar + grid ================= --}}
    <div class="cartelera-layout">

        {{-- Fons fosc quan el panell està obert en mòbil --}}
        <div class="filters-backdrop" id="filtersBackdrop" aria-hidden="true"></div>

        {{-- ============ SIDEBAR DE FILTRES ============ --}}
        <aside class="cartelera-sidebar" id="filtersPanel">

            <div class="flex justify-between items-center mb-5">
                <span class="text-xs font-black uppercase tracking-[0.2em]">Filters</span>
                <div class="flex items-center gap-3">
                    @if($filtered)
                        <a href="{{ route('peliculas.index') }}"
                           class="text-cinema-accent text-xs font-bold hover:opacity-70 transition-opacity">
                            Clear &times;
                        </a>
                    @endif
                    {{-- Close (mobile only) --}}
                    <button type="button" id="filtersClose" class="filters-close" aria-label="Close filters">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            {{-- DIA --}}
            <p class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-[color:var(--color-text-secondary)] mb-2">Day</p>
            @if($filterDates->isEmpty())
                <p class="text-[color:var(--color-text-secondary)] text-xs mb-4">No screenings scheduled</p>
            @else
                <select name="dia" onchange="document.getElementById('filterForm').submit()"
                        class="form-select text-sm py-1.5 mb-4">
                    <option value="">-- All days --</option>
                    @foreach($filterDates as $dia)
                        <option value="{{ $dia }}" {{ request('dia') == $dia ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($dia)->locale('en')->isoFormat('ddd D MMM') }}
                        </option>
                    @endforeach
                </select>
            @endif

            <div class="h-px bg-black/10 my-4"></div>

            {{-- HORARI --}}
            <p class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-[color:var(--color-text-secondary)] mb-3">Time</p>
            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <span id="lblDesde" class="text-cinema-accent text-sm font-bold">
                        {{ str_pad(request('hora_desde', 0), 2, '0', STR_PAD_LEFT) }}:00
                    </span>
                    <span class="text-[color:var(--color-text-secondary)] text-xs opacity-50">&ndash;</span>
                    <span id="lblHasta" class="text-cinema-accent text-sm font-bold">
                        {{ str_pad(request('hora_hasta', 23), 2, '0', STR_PAD_LEFT) }}:00
                    </span>
                </div>
                <div class="relative h-5">
                    <input type="range" name="hora_desde" id="rangeDesde"
                           min="0" max="23" step="1" value="{{ request('hora_desde', 0) }}"
                           oninput="syncHoraRange()" onchange="document.getElementById('filterForm').submit()"
                           class="absolute w-full accent-cinema-accent">
                    <input type="range" name="hora_hasta" id="rangeHasta"
                           min="0" max="23" step="1" value="{{ request('hora_hasta', 23) }}"
                           oninput="syncHoraRange()" onchange="document.getElementById('filterForm').submit()"
                           class="absolute w-full accent-cinema-accent">
                </div>
            </div>

            <div class="h-px bg-black/10 my-4"></div>

            {{-- CINEMA --}}
            <p class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-[color:var(--color-text-secondary)] mb-3">Cinema</p>
            @foreach($filterCines as $cine)
                <label class="flex items-center gap-2.5 cursor-pointer py-1.5 group">
                    <input type="radio" name="cine_id" value="{{ $cine->id }}"
                           {{ request('cine_id') == $cine->id ? 'checked' : '' }}
                           onchange="document.getElementById('filterForm').submit()"
                           class="accent-cinema-accent w-4 h-4 flex-shrink-0">
                    <span class="text-sm leading-tight group-hover:text-cinema-accent transition-colors">
                        {{ $cine->nombre }}
                        <span class="block text-[color:var(--color-text-secondary)] text-xs">{{ $cine->ciudad }}</span>
                    </span>
                </label>
            @endforeach

            <div class="h-px bg-black/10 my-4"></div>

            {{-- GÈNERE --}}
            <p class="text-[0.65rem] font-black uppercase tracking-[0.2em] text-[color:var(--color-text-secondary)] mb-3">Genre</p>
            @foreach($filterCats as $cat)
                <label class="flex items-center gap-2.5 cursor-pointer py-1 group">
                    <input type="radio" name="categoria_id" value="{{ $cat->id }}"
                           {{ request('categoria_id') == $cat->id ? 'checked' : '' }}
                           onchange="document.getElementById('filterForm').submit()"
                           class="accent-cinema-accent w-4 h-4 flex-shrink-0">
                    <span class="text-sm group-hover:text-cinema-accent transition-colors">{{ $cat->nombre }}</span>
                </label>
            @endforeach

            {{-- Apply (mobile only, closes the panel) --}}
            <button type="submit" class="btn btn-primary btn-full filters-apply">
                View {{ $peliculas->count() }} results
            </button>

        </aside>

        {{-- ============ MOVIE GRID ============ --}}
        <div>
            <div class="grid reveal-stagger"
                 style="grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 1.75rem 1.5rem;">
                @forelse($peliculas as $pelicula)
                    <div class="card-movie">
                        <div class="card-movie-poster">
                            <img src="{{ $pelicula->poster_url }}"
                                 alt="Poster for {{ $pelicula->titulo }}"
                                 loading="lazy">

                            <div class="card-movie-overlay">
                                <div class="text-center px-3 flex flex-col gap-2 w-full">
                                    <a href="{{ route('peliculas.show', $pelicula) }}{{ request('cine_id') ? '?cine_id='.request('cine_id') : '' }}"
                                       class="btn btn-secondary btn-sm w-full">Details</a>
                                    <a href="{{ route('peliculas.show', $pelicula) }}{{ request('cine_id') ? '?cine_id='.request('cine_id') : '' }}"
                                       class="btn btn-primary btn-sm w-full">Buy Tickets</a>
                                </div>
                            </div>

                            @if($pelicula->categorias->first())
                                <div class="card-movie-badge">{{ $pelicula->categorias->first()->nombre }}</div>
                            @endif
                        </div>

                        <div class="card-movie-body">
                            <div class="card-movie-title">{{ $pelicula->titulo }}</div>
                            <div class="card-movie-meta">
                                @if($pelicula->rating)
                                    <span class="cm-rating"><i class="fas fa-star"></i>{{ number_format($pelicula->rating, 1) }}</span>
                                    <span class="cm-sep">·</span>
                                @endif
                                <span>{{ $pelicula->duracion_min ?? '?' }} min</span>
                                <span class="cm-sep">·</span>
                                <span>{{ $pelicula->classificacio_edad ?? 'TP' }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-16 text-[color:var(--color-text-secondary)]">
                        <i class="fas fa-film text-5xl opacity-20 block mb-4"></i>
                        @if(request('q'))
                            We couldn't find any movie for “<span class="font-bold text-cinema-accent">{{ request('q') }}</span>”.
                        @else
                            No movies match the selected filters.
                        @endif
                        <br>
                        <a href="{{ route('peliculas.index') }}"
                           class="text-cinema-accent text-sm mt-3 inline-block hover:opacity-80 transition-opacity">
                            View all &rarr;
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

    </div>{{-- /cartelera-layout --}}

    </form>

</div>

<script>
function syncHoraRange() {
    var d = parseInt(document.getElementById('rangeDesde').value);
    var h = parseInt(document.getElementById('rangeHasta').value);
    if (h < d) { document.getElementById('rangeHasta').value = d; h = d; }
    var pad = (n) => String(n).padStart(2, '0');
    document.getElementById('lblDesde').textContent = pad(d) + ':00';
    document.getElementById('lblHasta').textContent = pad(h) + ':00';
}
document.addEventListener('DOMContentLoaded', function () {
    syncHoraRange();

    // Panell de filtres (mòbil/tablet)
    const panel    = document.getElementById('filtersPanel');
    const backdrop = document.getElementById('filtersBackdrop');
    const openBtn  = document.getElementById('filtersToggle');
    const closeBtn = document.getElementById('filtersClose');

    function openFilters() {
        panel.classList.add('is-open');
        backdrop.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
    function closeFilters() {
        panel.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    openBtn?.addEventListener('click', openFilters);
    closeBtn?.addEventListener('click', closeFilters);
    backdrop?.addEventListener('click', closeFilters);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeFilters(); });
});
</script>
@endsection
