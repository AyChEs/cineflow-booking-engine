@extends('layout')
@section('title', 'Crear cuenta')
@section('content')

<div class="auth-container">
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

            <button type="submit" class="btn btn-primary btn-full btn-lg">Crear cuenta</button>
        </form>

        <div class="auth-footer">
            <p>¿Ya tienes cuenta?</p>
            <a href="{{ route('login') }}" class="auth-link">Entrar</a>
        </div>
    </div>
</div>

@endsection
