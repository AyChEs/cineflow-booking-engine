@extends('layout')
@section('title', 'Reset Password')
@section('content')

<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">RESET PASSWORD</h1>
        <p class="auth-subtitle">Enter your new password</p>

        <form method="POST" action="{{ route('password.store') }}" class="auth-form">
            @csrf

            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <!-- Email Address -->
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                       class="form-input" required autofocus autocomplete="username" readonly>
                @error('email')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password" class="form-label">New Password</label>
                <input id="password" type="password" name="password"
                       class="form-input" required autocomplete="new-password">
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                       class="form-input" required autocomplete="new-password">
                @error('password_confirmation')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                RESET PASSWORD
            </button>
        </form>
    </div>
</div>
@endsection
