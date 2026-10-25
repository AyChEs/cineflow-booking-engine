@extends('layout')

@section('title', 'Acceso denegado')

@section('content')
<div class="mx-auto max-w-xl px-4 py-16 text-center sm:py-24">
    <p class="display cifras text-6xl text-carmin-400">403</p>
    <h1 class="display mt-2 text-3xl text-texto">No tienes acceso a esta página</h1>
    <p class="mt-4 text-texto-2">Esta sección es solo para el personal del cine.</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3">
        <a href="{{ url('/') }}" class="btn btn-primary">Ir a la portada</a>
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Mi panel</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-secondary">Entrar</a>
        @endauth
    </div>
</div>
@endsection
