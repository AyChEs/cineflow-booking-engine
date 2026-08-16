@extends('layout')
@section('title', 'Verify email')
@section('content')

<div class="auth-container">
    <div class="auth-card">

        <h1 class="auth-title">VERIFY YOUR EMAIL</h1>
        <p class="auth-subtitle">
            Thanks for signing up! Before getting started, please verify your email address by
            clicking the link we just emailed you. Didn't get it? We can send another one.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success">
                A new verification link has been sent to the email address you provided during registration.
            </div>
        @endif

        <div style="display:flex; align-items:center; justify-content:space-between; margin-top:1.5rem; gap:1rem; flex-wrap:wrap;">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane" style="margin-right:0.5rem;"></i>Resend verification email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="auth-link" style="background:none;border:none;cursor:pointer;font-size:0.875rem;">
                    Log out
                </button>
            </form>
        </div>

    </div>
</div>

@endsection
