@extends('layout')

@section('title', '403 – Access Denied')

@section('content')
<div style="max-width: 600px; margin: 80px auto; text-align: center; padding: 40px; border: 1px solid #f5c6cb; background: #fff3f3; border-radius: 8px;">
    <h1 style="font-size: 72px; margin: 0; color: #dc3545;">403</h1>
    <h2 style="color: #721c24;">Access Denied</h2>
    <p style="color: #555; margin: 20px 0;">
        You do not have permission to access this section.
        You need the <strong>admin</strong> role to continue.
    </p>
    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 30px;">
        <a href="{{ url('/') }}" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;">
            Home
        </a>
        @auth
        <a href="{{ route('dashboard') }}" style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 3px;">
            Dashboard
        </a>
        @else
        <a href="{{ route('login') }}" style="padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 3px;">
            Log In
        </a>
        @endauth
    </div>
</div>
@endsection
