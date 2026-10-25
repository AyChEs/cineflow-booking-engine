@extends('layout')
@section('title', 'Crear cuenta')
@section('content')

<div class="auth-container">
    <div class="auth-fondo" aria-hidden="true">
        <x-marca.imagen nombre="sala" sizes="100vw" />
    </div>
    <div class="auth-card">
        <h1 class="auth-title">Crear cuenta</h1>
        <p class="auth-subtitle">Guarda tus entradas y compra más rápido.</p>

        <form method="POST" action="{{ route('register') }}" class="auth-form" novalidate>
            @csrf

            <x-campo name="name" label="Nombre" required autofocus autocomplete="given-name" />
            <x-campo name="apellidos" label="Apellidos" :opcional="true" autocomplete="family-name" />
            <x-campo name="email" label="Correo electrónico" type="email" required autocomplete="email" />
            <x-campo name="telefono" label="Teléfono" type="tel" :opcional="true" autocomplete="tel" />
            <x-campo name="password" label="Contraseña" type="password" required autocomplete="new-password" ayuda="Al menos 8 caracteres." />
            <x-campo name="password_confirmation" label="Repite la contraseña" type="password" required autocomplete="new-password" />

            {{-- Consentimiento (RGPD): casilla obligatoria, validada también en el servidor. --}}
            @php($errorPrivacidad = $errors->first('privacidad'))
            <div class="form-group">
                <label for="privacidad" class="flex min-h-11 cursor-pointer items-start gap-3 text-sm text-texto">
                    <input type="checkbox" name="privacidad" id="privacidad" value="1" required @checked(old('privacidad'))
                           @if ($errorPrivacidad) aria-invalid="true" aria-describedby="privacidad-error" @endif
                           class="mt-0.5 h-5 w-5 shrink-0 rounded border-libre bg-sala-900 text-carmin-600 focus:ring-foco focus:ring-offset-sala-900">
                    <span>He leído la <a href="{{ route('legal.privacidad') }}" target="_blank" rel="noopener" class="auth-link">política de privacidad<span class="sr-only"> (se abre en otra pestaña)</span></a>.</span>
                </label>
                @if ($errorPrivacidad)
                    <p id="privacidad-error" class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />{{ $errorPrivacidad }}</p>
                @endif
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg">Crear cuenta</button>
        </form>

        <div class="auth-footer">
            <p>¿Ya tienes cuenta?</p>
            <a href="{{ route('login') }}" class="auth-link">Entrar</a>
        </div>
    </div>
</div>

@endsection
