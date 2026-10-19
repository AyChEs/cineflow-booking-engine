@extends('layout')
@section('title', 'Verifica tu correo')
@section('content')

<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Verifica tu correo</h1>
        <p class="auth-subtitle">
            Te hemos enviado un enlace para confirmar tu correo. Si no te ha llegado, te mandamos otro.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success" role="status">
                <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" />Te hemos enviado un enlace nuevo.
            </div>
        @endif

        <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <x-icon name="send" class="h-4 w-4" />Enviar otro enlace
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-secondary">Salir</button>
            </form>
        </div>
    </div>
</div>

@endsection
