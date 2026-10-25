@extends('layout')

@section('title', 'Mi cuenta')

@section('content')
@php $usuario = Auth::user(); @endphp
<div class="mx-auto max-w-5xl px-4 py-pagina sm:px-6">
    <h1 class="display text-4xl text-texto">Hola, {{ $usuario->name }}</h1>

    @if (session('error'))
        <div class="alert alert-error mt-6" role="alert"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />{{ session('error') }}</div>
    @endif

    <div class="mt-8 grid gap-4 sm:grid-cols-[1fr,1fr]">
        <section class="card flex flex-col gap-3" aria-labelledby="resumen-entradas">
            <h2 id="resumen-entradas" class="text-sm font-semibold text-texto-2">Entradas compradas</h2>
            <p class="display cifras text-5xl text-texto">{{ $total }}</p>
            <div class="mt-auto flex flex-wrap gap-3">
                <a href="{{ route('reservas.mis') }}" class="btn btn-secondary">Ver mis entradas</a>
                <a href="{{ route('peliculas.index') }}" class="btn btn-primary"><x-icon name="ticket" class="h-4 w-4" />Comprar entradas</a>
            </div>
        </section>
        <section class="card flex flex-col gap-3" aria-labelledby="tu-cuenta">
            <h2 id="tu-cuenta" class="text-sm font-semibold text-texto-2">Tu cuenta</h2>
            <dl class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 text-sm">
                <dt class="text-texto-2">Nombre</dt><dd class="text-texto">{{ $usuario->name }} {{ $usuario->apellidos }}</dd>
                <dt class="text-texto-2">Correo</dt><dd class="break-all text-texto">{{ $usuario->email }}</dd>
                <dt class="text-texto-2">Teléfono</dt><dd class="text-texto">{{ $usuario->telefono ?: 'Sin teléfono' }}</dd>
            </dl>
            <a href="{{ route('profile.edit') }}" class="btn btn-secondary mt-auto self-start"><x-icon name="user" class="h-4 w-4" />Editar el perfil</a>
        </section>
    </div>

    <section class="mt-10" aria-labelledby="ultimas">
        <h2 id="ultimas" class="mb-4 text-lg font-semibold text-texto">Últimas entradas</h2>
        @if ($reservas->isEmpty())
            <p class="text-texto-2">Todavía no tienes entradas. <a href="{{ route('peliculas.index') }}" class="font-semibold text-texto underline">Mira la cartelera</a>.</p>
        @else
            <ul class="divide-y divide-sala-700 rounded-card border border-sala-700 bg-sala-900">
                @foreach ($reservas as $reserva)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-5">
                        <div class="min-w-0">
                            <a href="{{ route('reservas.show', $reserva) }}" class="font-semibold text-texto hover:text-carmin-400">{{ $reserva->sesion->pelicula->titulo ?? 'Sesión borrada' }}</a>
                            <p class="cifras text-sm text-texto-2">
                                {{ $reserva->sesion?->fecha_hora?->locale('es')->isoFormat('D [de] MMMM, H:mm') }} · <span class="text-marquesina">{{ $reserva->butaques_seleccionades }}</span>
                            </p>
                        </div>
                        <x-estado-reserva :estat="$reserva->estat" />
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('reservas.mis') }}" class="mt-4 inline-flex min-h-11 items-center gap-1 font-semibold text-texto underline">Todas mis entradas<x-icon name="arrow-right" class="h-4 w-4" /></a>
        @endif
    </section>
</div>
@endsection
