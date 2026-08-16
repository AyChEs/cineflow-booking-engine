@extends('layout')
@section('title', 'Create account')
@section('content')

<div class="auth-container">
    <div class="auth-card">

        <h1 class="auth-title">CREATE ACCOUNT</h1>
        <p class="auth-subtitle">Join CineFlow</p>

        <form method="POST" action="{{ route('register') }}" class="auth-form">
            @csrf

            {{-- Name --}}
            <div class="form-group">
                <label for="name" class="form-label">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}"
                       class="form-input" required autofocus autocomplete="name"
                       placeholder="Your name">
                @error('name')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Last name --}}
            <div class="form-group">
                <label for="apellidos" class="form-label">Last name</label>
                <input id="apellidos" type="text" name="apellidos" value="{{ old('apellidos') }}"
                       class="form-input" autocomplete="family-name"
                       placeholder="Your last name">
                @error('apellidos')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="form-input" required autocomplete="username"
                       placeholder="name@example.com">
                @error('email')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Phone (optional) --}}
            <div class="form-group">
                <label for="telefono" class="form-label">
                    Phone
                    <span style="color:var(--color-text-secondary); font-weight:400; font-size:0.78rem;">(optional)</span>
                </label>
                <input id="telefono" type="tel" name="telefono" value="{{ old('telefono') }}"
                       class="form-input" autocomplete="tel"
                       placeholder="612 345 678">
                @error('telefono')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" name="password"
                       class="form-input" required autocomplete="new-password"
                       placeholder="Minimum 8 characters">
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm password --}}
            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                       class="form-input" required autocomplete="new-password"
                       placeholder="Repeat the password">
                @error('password_confirmation')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-user-plus" style="margin-right:0.5rem;"></i>Create account
            </button>
        </form>

        <div class="auth-footer">
            <p>Already have an account?</p>
            <a href="{{ route('login') }}" class="auth-link">Sign in →</a>
        </div>

    </div>
</div>

@endsection
