@extends('layout')
@section('title', 'Nueva contraseña')
@section('content')

<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Nueva contraseña</h1>
        <p class="auth-subtitle">Elige la contraseña con la que entrarás a partir de ahora.</p>

        <form method="POST" action="{{ route('password.store') }}" class="auth-form" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <x-campo name="email" label="Correo electrónico" type="email" :value="$request->email" required readonly autocomplete="username" />
            <x-campo name="password" label="Contraseña nueva" type="password" required autofocus autocomplete="new-password" ayuda="Al menos 8 caracteres." />
            <x-campo name="password_confirmation" label="Repite la contraseña" type="password" required autocomplete="new-password" />

            <button type="submit" class="btn btn-primary btn-full btn-lg">Guardar la contraseña</button>
        </form>
    </div>
</div>

@endsection
