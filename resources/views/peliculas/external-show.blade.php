@extends('layout')

@section('title', $movie['title'])

@section('content')
<div class="container max-w-5xl py-10">

    {{-- Breadcrumb / back --}}
    <a href="{{ route('peliculas.external.index') }}"
       class="inline-flex items-center gap-2 text-carmin-400 font-bold text-sm mb-8 hover:opacity-80 transition-opacity">
        <x-icon name="arrow-left" class="h-4 w-4" /> Back to API Listings
    </a>

    {{-- Movie layout: poster + info --}}
    <div class="grid gap-8 items-start" style="grid-template-columns: 260px 1fr;">

        {{-- Poster --}}
        <div class="aspect-[2/3] bg-sala-900 rounded-2xl overflow-hidden border border-white/10 shadow-2xl">
            <img src="{{ $movie['image_url'] ?: 'https://placehold.co/600x900/1a1a1a/d4183d?text=No+Image' }}"
                 alt="Poster for {{ $movie['title'] }}"
                 class="w-full h-full object-cover block">
        </div>

        {{-- Information --}}
        <div>
            {{-- Badge API externa --}}
            <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest
                         bg-carmin-600/20 text-carmin-400 border border-carmin-400/40
                         px-3 py-1 rounded-full mb-4">
                <x-icon name="satellite" class="h-3 w-3" /> TMDB API
            </span>

            <h1 class="text-4xl font-black uppercase leading-tight mb-4">{{ $movie['title'] }}</h1>

            {{-- Badges metadata --}}
            <div class="flex flex-wrap gap-2 mb-6">
                @if($movie['genre'])
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                                 bg-carmin-600/20 text-carmin-400 border border-carmin-400/40">
                        {{ $movie['genre'] }}
                    </span>
                @endif
                @if($movie['year'])
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                 bg-white/5 text-[color:var(--color-text-secondary)] border border-white/10">
                        <x-icon name="calendar" class="h-4 w-4 mr-1 opacity-60" />{{ $movie['year'] }}
                    </span>
                @endif
                <span class="px-3 py-1 rounded-full text-xs font-semibold
                             bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">
                    <x-icon name="star" class="h-4 w-4 mr-1" />
                    {{ number_format((float) ($movie['stars'] ?? 0), 1) }} / 5
                </span>
            </div>

            {{-- Sinopsis --}}
            <div class="mb-8">
                <h2 class="text-xs font-bold uppercase tracking-widest text-[color:var(--color-text-secondary)] mb-3">Synopsis</h2>
                <p class="text-[color:var(--color-text-secondary)] leading-relaxed text-[0.95rem] max-w-2xl">
                    {{ $movie['description'] ?: 'No description available.' }}
                </p>
            </div>

            {{-- Technical info (TMDB ID) --}}
            <div class="p-4 rounded-xl bg-sala-900 border border-white/10 mb-8 text-sm">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-widest text-texto-2 mb-0.5">TMDB ID</p>
                        <p class="font-semibold">{{ $movie['id'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-widest text-texto-2 mb-0.5">Rating</p>
                        <p class="font-semibold">
                            <span class="text-yellow-400">★</span>
                            {{ number_format((float) ($movie['stars'] ?? 0), 1) }} / 5
                        </p>
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('peliculas.external.index') }}" class="btn btn-primary">
                    <x-icon name="compass" class="h-4 w-4 mr-1.5" /> Keep Exploring
                </a>
                <a href="{{ route('peliculas.index') }}" class="btn btn-secondary">
                    <x-icon name="film" class="h-4 w-4 mr-1.5" /> Local Listings
                </a>
                @auth
                    @if(auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('admin.peliculas.external.sync-local') }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <x-icon name="refresh" class="h-4 w-4 mr-1" /> Sync to Local
                            </button>
                        </form>
                    @endif
                @endauth
            </div>
        </div>

    </div>

</div>
@endsection
