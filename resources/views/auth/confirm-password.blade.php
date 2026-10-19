@extends('layout')
@section('title', 'Confirma tu contraseña')
@section('content')

<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Confirma tu contraseña</h1>
        <p class="auth-subtitle">Es una zona protegida. Escribe tu contraseña para seguir.</p>

        <form method="POST" action="{{ route('password.confirm') }}" class="auth-form" novalidate>
            @csrf
            <x-campo name="password" label="Contraseña" type="password" required autofocus autocomplete="current-password" />
            <button type="submit" class="btn btn-primary btn-full btn-lg">
                <x-icon name="lock" class="h-4 w-4" />Confirmar
            </button>
        </form>
    </div>
</div>

@endsection
