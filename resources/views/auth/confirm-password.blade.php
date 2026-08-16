@extends('layout')
@section('title', 'Confirm password')
@section('content')

<div class="auth-container">
    <div class="auth-card">

        <h1 class="auth-title">CONFIRM PASSWORD</h1>
        <p class="auth-subtitle">This is a secure area. Please confirm your password before continuing.</p>

        <form method="POST" action="{{ route('password.confirm') }}" class="auth-form">
            @csrf

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" name="password"
                       class="form-input" required autocomplete="current-password"
                       placeholder="••••••••" autofocus>
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-lock" style="margin-right:0.5rem;"></i>Confirm
            </button>
        </form>

    </div>
</div>

@endsection
