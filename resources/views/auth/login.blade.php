@extends('layout')
@section('title', 'Entrar')
@section('content')

<div class="auth-container">
    <div class="auth-fondo" aria-hidden="true">
        <x-marca.imagen nombre="sala" sizes="100vw" />
    </div>
    <div class="auth-card">
        <h1 class="auth-title">Entrar</h1>
        <p class="auth-subtitle">Accede para ver tus entradas.</p>

        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
            @csrf

            <x-campo name="email" label="Correo electrónico" type="email" required autofocus autocomplete="username" />
            <x-campo name="password" label="Contraseña" type="password" required autocomplete="current-password" />

            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <label for="remember_me" class="inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm text-texto-2">
                    <input type="checkbox" name="remember" id="remember_me"
                           class="h-5 w-5 rounded border-libre bg-sala-900 text-carmin-600 focus:ring-foco focus:ring-offset-sala-900">
                    Recordarme
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-link text-sm">¿Has olvidado la contraseña?</a>
                @endif
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg">Entrar</button>
        </form>

        <div class="auth-footer">
            <p>¿No tienes cuenta?</p>
            <a href="{{ route('register') }}" class="auth-link">Crear una cuenta</a>
        </div>

        <section class="mt-6 rounded-control border border-sala-700 p-4 text-sm" aria-labelledby="demo-cuentas">
            <h2 id="demo-cuentas" class="mb-2 font-semibold text-texto">Cuentas de la demo</h2>
            <dl class="grid grid-cols-[auto,1fr] gap-x-3 gap-y-1 text-texto-2">
                <dt>Administración</dt><dd><code class="text-texto">admin@cineflow.test</code> · <code class="text-texto">admin1234</code></dd>
                <dt>Taquilla</dt><dd><code class="text-texto">taquilla@cineflow.test</code> · <code class="text-texto">taquilla1234</code></dd>
                <dt>Cliente</dt><dd><code class="text-texto">cliente@cineflow.test</code> · <code class="text-texto">cliente1234</code></dd>
            </dl>
        </section>
    </div>
</div>

@endsection
