@extends('layout')

@section('mov', 'sitio')

@section('title', 'Cartelera')

@section('content')
@php
    $total = $peliculas->count();
    $resumen = $total === 1 ? '1 película' : "{$total} películas";
@endphp
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 md:py-14">

    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="display text-4xl text-texto md:text-5xl">Cartelera</h1>
            <p class="cifras mt-2 text-texto-2" aria-live="polite" id="resultado">
                {{ $resumen }}@if ($filtered) con los filtros elegidos @endif
            </p>
        </div>
        @auth
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.peliculas.index') }}" class="btn btn-secondary btn-sm">
                    <x-icon name="settings" class="h-4 w-4" />Gestionar películas
                </a>
            @endif
        @endauth
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status"><x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" />{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error" role="alert"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('peliculas.index') }}" role="search" aria-label="Filtrar la cartelera"
          class="mb-10 grid gap-4 rounded-card border border-sala-700 bg-sala-900 p-4 sm:grid-cols-2 sm:p-6 lg:grid-cols-[2fr,1fr,1fr,1fr,1fr]">
        <div>
            <label for="f-q" class="form-label">Buscar</label>
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-texto-2" />
                <input id="f-q" type="search" name="q" value="{{ request('q') }}" placeholder="Título de la película"
                       class="form-input pl-9" autocomplete="off">
            </div>
        </div>
        <div>
            <label for="f-categoria" class="form-label">Género</label>
            <select id="f-categoria" name="categoria_id" class="form-select">
                <option value="">Todos</option>
                @foreach ($filterCats as $cat)
                    <option value="{{ $cat->id }}" @selected(request('categoria_id') == $cat->id)>{{ $cat->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-cine" class="form-label">Cine</label>
            <select id="f-cine" name="cine_id" class="form-select">
                <option value="">Todos</option>
                @foreach ($filterCines as $cine)
                    <option value="{{ $cine->id }}" @selected(request('cine_id') == $cine->id)>{{ $cine->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-dia" class="form-label">Día</label>
            <select id="f-dia" name="dia" class="form-select">
                <option value="">Cualquiera</option>
                @foreach ($filterDates as $dia)
                    <option value="{{ $dia }}" @selected(request('dia') == $dia)>{{ ucfirst(\Carbon\Carbon::parse($dia)->locale('es')->isoFormat('dddd D [de] MMMM')) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-orden" class="form-label">Ordenar por</label>
            <select id="f-orden" name="orden" class="form-select">
                <option value="rating" @selected($orden === 'rating')>Mejor valoradas</option>
                <option value="titulo" @selected($orden === 'titulo')>Título</option>
                <option value="durada" @selected($orden === 'durada')>Duración</option>
            </select>
        </div>
        <div class="flex flex-wrap items-center gap-3 sm:col-span-2 lg:col-span-5">
            <button type="submit" class="btn btn-primary"><x-icon name="sliders" class="h-4 w-4" />Filtrar</button>
            @if ($filtered || request()->filled('orden'))
                <a href="{{ route('peliculas.index') }}" class="btn btn-secondary">Quitar filtros</a>
            @endif
        </div>
    </form>

    @if ($peliculas->isEmpty())
        <div class="rounded-card border border-sala-700 bg-sala-900 px-6 py-12 text-center">
            <h2 class="text-lg font-semibold text-texto">No hay películas con estos filtros</h2>
            <p class="mt-2 text-texto-2">Prueba con otro día u otro cine.</p>
            <a href="{{ route('peliculas.index') }}" class="btn btn-secondary mt-6">Quitar filtros</a>
        </div>
    @else
        <h2 class="sr-only">Películas</h2>
        <ul class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-5 lg:gap-x-6">
            @foreach ($peliculas as $pelicula)
                <li><x-movie-card :pelicula="$pelicula" /></li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
