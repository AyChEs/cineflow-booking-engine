@extends('layout')
@section('title', 'Mi perfil')

@section('content')
@php
    $usuario = Auth::user();
    $volver = $usuario->canManage() ? route('dashboard') : route('cliente.dashboard');
    $iniciales = mb_strtoupper(mb_substr($usuario->name, 0, 1).mb_substr($usuario->apellidos ?? '', 0, 1));
@endphp
<div class="container max-w-5xl py-10">

    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <h1 class="page-title mb-0">Mi perfil</h1>
        <a href="{{ $volver }}" class="btn btn-secondary btn-sm">
            <x-icon name="arrow-left" class="h-4 w-4" />Volver al panel
        </a>
    </div>

    @if (session('status') === 'profile-updated')
        <div class="alert alert-success" role="status"><x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" />Datos guardados.</div>
    @elseif (session('status') === 'password-updated')
        <div class="alert alert-success" role="status"><x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" />Contraseña cambiada.</div>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-[16rem,1fr]">

        <aside class="card flex flex-col gap-4" aria-label="Resumen de tu cuenta">
            <div class="flex items-center gap-3 lg:flex-col lg:text-center">
                <span class="display flex h-16 w-16 shrink-0 items-center justify-center rounded-full border border-sala-700 bg-sala-850 text-2xl text-texto" aria-hidden="true">{{ $iniciales }}</span>
                <div class="min-w-0">
                    <p class="font-semibold text-texto">{{ $usuario->name }} {{ $usuario->apellidos }}</p>
                    <p class="truncate text-sm text-texto-2">{{ $usuario->email }}</p>
                </div>
            </div>
            <dl class="grid grid-cols-[1fr,auto] gap-y-2 border-t border-sala-700 pt-4 text-sm">
                <dt class="text-texto-2">Entradas compradas</dt>
                <dd class="cifras font-semibold text-texto">{{ $usuario->reservas()->count() }}</dd>
                <dt class="text-texto-2">Cliente desde</dt>
                <dd class="text-texto">{{ $usuario->created_at->locale('es')->isoFormat('MMMM [de] YYYY') }}</dd>
            </dl>
            <a href="{{ route('reservas.mis') }}" class="btn btn-secondary btn-sm">
                <x-icon name="ticket" class="h-4 w-4" />Mis entradas
            </a>
        </aside>

        <div class="flex flex-col gap-6">
            <section class="card" aria-labelledby="datos">
                <h2 id="datos" class="mb-5 text-lg font-semibold text-texto">Datos personales</h2>
                <form method="POST" action="{{ route('profile.update') }}" novalidate>
                    @csrf
                    @method('PATCH')
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <x-campo name="name" label="Nombre" :value="$usuario->name" required autocomplete="given-name" />
                        <x-campo name="apellidos" label="Apellidos" :value="$usuario->apellidos" :opcional="true" autocomplete="family-name" />
                    </div>
                    <x-campo name="email" label="Correo electrónico" type="email" :value="$usuario->email" required autocomplete="email" />
                    <x-campo name="telefono" label="Teléfono" type="tel" :value="$usuario->telefono" :opcional="true" autocomplete="tel" />
                    <button type="submit" class="btn btn-primary"><x-icon name="save" class="h-4 w-4" />Guardar</button>
                </form>
            </section>

            <section class="card" aria-labelledby="clave">
                <h2 id="clave" class="mb-5 text-lg font-semibold text-texto">Cambiar la contraseña</h2>
                <form method="POST" action="{{ route('password.update') }}" novalidate>
                    @csrf
                    @method('PUT')
                    <x-campo name="current_password" label="Contraseña actual" type="password" bag="updatePassword" autocomplete="current-password" />
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <x-campo name="password" id="password_nueva" label="Contraseña nueva" type="password" bag="updatePassword" autocomplete="new-password" />
                        <x-campo name="password_confirmation" id="password_nueva_confirmacion" label="Repite la contraseña" type="password" bag="updatePassword" autocomplete="new-password" />
                    </div>
                    <button type="submit" class="btn btn-secondary"><x-icon name="key" class="h-4 w-4" />Cambiar la contraseña</button>
                </form>
            </section>

            <section class="card border-error/40" aria-labelledby="borrar">
                <h2 id="borrar" class="mb-2 text-lg font-semibold text-error">Borrar la cuenta</h2>
                <p class="mb-5 text-sm text-texto-2">Se borran tus datos y no se pueden recuperar.</p>
                <button type="button" class="btn btn-danger" id="abrir-borrar" aria-haspopup="dialog">
                    <x-icon name="trash" class="h-4 w-4" />Borrar la cuenta
                </button>
            </section>
        </div>
    </div>
</div>

<dialog id="dialogo-borrar" aria-labelledby="dialogo-borrar-titulo"
        class="w-[min(28rem,calc(100%-2rem))] rounded-card border border-sala-700 bg-sala-900 p-6 text-texto backdrop:bg-sala-950/80">
    <h2 id="dialogo-borrar-titulo" class="mb-2 text-lg font-semibold text-error">¿Borrar la cuenta?</h2>
    <p class="mb-5 text-sm text-texto-2">Escribe tu contraseña para confirmarlo.</p>
    <form method="POST" action="{{ route('profile.destroy') }}" novalidate>
        @csrf
        @method('DELETE')
        <x-campo name="password" id="password_borrar" label="Contraseña" type="password" bag="userDeletion" autocomplete="current-password" />
        <div class="flex flex-wrap justify-end gap-3">
            <button type="button" class="btn btn-secondary" id="cerrar-borrar">Cancelar</button>
            <button type="submit" class="btn btn-danger"><x-icon name="trash" class="h-4 w-4" />Borrar la cuenta</button>
        </div>
    </form>
</dialog>

<script>
(function () {
    const dialogo = document.getElementById('dialogo-borrar');
    const abrir = document.getElementById('abrir-borrar');
    abrir.addEventListener('click', () => dialogo.showModal());
    document.getElementById('cerrar-borrar').addEventListener('click', () => dialogo.close());
    dialogo.addEventListener('close', () => abrir.focus());
    @if ($errors->userDeletion->isNotEmpty())
        dialogo.showModal();
    @endif
})();
</script>
@endsection
