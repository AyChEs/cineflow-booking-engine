@extends('layout')

@section('mov', 'sitio')

@section('title', 'Cines')

@section('content')
<header class="cines-cabecera">
    {{-- El vestíbulo de noche es la imagen más grande de la página: carga ya y con prioridad --}}
    <x-marca.imagen nombre="vestibulo" sizes="100vw" class="cines-cabecera-img" :prioridad="true" />
    <div class="cines-cabecera-texto mx-auto max-w-7xl px-4 sm:px-6">
        <h1 class="display text-4xl text-texto md:text-5xl">Cines</h1>
        <p class="cifras mt-2 text-texto-2">{{ $cines->count() }} {{ $cines->count() === 1 ? 'cine' : 'cines' }}</p>
    </div>
</header>

<div class="mx-auto max-w-7xl px-4 pb-10 pt-6 sm:px-6 md:pb-14">

    @if (session('success'))
        <div class="alert alert-success mt-6" role="status"><x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" />{{ session('success') }}</div>
    @endif

    @if ($cines->isEmpty())
        <p class="mt-10 text-texto-2">Todavía no hay cines.</p>
    @else
        <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($cines as $cine)
                <li>
                    <article class="relative flex h-full flex-col gap-3 rounded-card border border-sala-700 bg-sala-900 p-6 transition-colors hover:border-carmin-400 hover:bg-sala-850">
                        <h2 class="display text-2xl text-texto">
                            <a href="{{ route('cines.show', $cine) }}" class="no-underline after:absolute after:inset-0 after:rounded-card after:content-['']">{{ $cine->nombre }}</a>
                        </h2>
                        <p class="flex items-start gap-1.5 text-sm text-texto-2">
                            <x-icon name="map-pin" class="mt-0.5 h-4 w-4 shrink-0" />
                            <span>{{ $cine->direccion_completa }}<br>{{ $cine->ciudad }}@if ($cine->provincia && $cine->provincia !== $cine->ciudad), {{ $cine->provincia }}@endif</span>
                        </p>
                        <p class="cifras mt-auto text-sm text-texto">{{ $cine->salas_count }} {{ $cine->salas_count === 1 ? 'sala' : 'salas' }}</p>
                    </article>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
