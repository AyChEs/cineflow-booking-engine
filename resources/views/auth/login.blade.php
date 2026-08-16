@extends('layout')
@section('title', 'Sign In')
@section('content')

<div class="auth-container">
    <div class="auth-card">

        <h1 class="auth-title">SIGN IN</h1>
        <p class="auth-subtitle">Welcome back to CineFlow</p>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="form-input" required autofocus autocomplete="username"
                       placeholder="name@example.com">
                @error('email')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" name="password"
                       class="form-input" required autocomplete="current-password"
                       placeholder="••••••••">
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                    <input type="checkbox" name="remember" id="remember_me"
                           style="accent-color: var(--color-accent-bright); width:1rem; height:1rem;">
                    <span style="font-size:0.875rem; color:var(--color-text-secondary);">Remember me</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-link" style="font-size:0.8rem;">
                        Forgot your password?
                    </a>
                @endif
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-sign-in-alt" style="margin-right:0.5rem;"></i>Sign In
            </button>
        </form>

        <div class="auth-footer">
            <p>Don't have an account?</p>
            <a href="{{ route('register') }}" class="auth-link">Create new account →</a>
        </div>

        <div style="margin-top:1.5rem; padding:0.75rem 1rem; border-radius:var(--radius-md, 10px); background:rgba(255,255,255,0.03); border:1px solid var(--border-subtle);">
            <p style="font-size:0.7rem; letter-spacing:0.05em; text-transform:uppercase; color:var(--color-text-secondary); font-weight:700; margin:0 0 0.5rem;">Demo accounts</p>
            <p style="font-size:0.8rem; margin:0.15rem 0;">Admin &middot; <code>admin@cineflow.test</code> &middot; <code>admin1234</code></p>
            <p style="font-size:0.8rem; margin:0.15rem 0;">Box office &middot; <code>taquilla@cineflow.test</code> &middot; <code>taquilla1234</code></p>
            <p style="font-size:0.8rem; margin:0.15rem 0;">Customer &middot; <code>cliente@cineflow.test</code> &middot; <code>cliente1234</code></p>
        </div>

    </div>
</div>

@endsection
