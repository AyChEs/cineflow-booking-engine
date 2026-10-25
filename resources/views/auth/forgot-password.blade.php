@extends('layout')
@section('title', 'Recuperar la contraseña')
@section('content')

<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Recuperar la contraseña</h1>
        <p class="auth-subtitle">Escribe tu correo y te mandamos un enlace para elegir otra.</p>

        @if (session('status'))
            <div class="alert alert-success" role="status">
                <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                <div>
                    <p>{{ session('status') }}</p>
                    @if (session('reset_link'))
                        <a href="{{ session('reset_link') }}" class="mt-2 inline-block font-semibold text-texto underline">Elegir una contraseña nueva</a>
                    @endif
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="auth-form" novalidate>
            @csrf
            <x-campo name="email" label="Correo electrónico" type="email" required autofocus autocomplete="email" />
            <button type="submit" class="btn btn-primary btn-full btn-lg">Enviar el enlace</button>
        </form>

        <div class="auth-footer">
            <a href="{{ route('login') }}" class="auth-link">Volver a entrar</a>
        </div>
    </div>
</div>

@endsection
