@extends('layout')
@section('title', 'My Profile')

@section('content')
<div class="container" style="max-width: 860px; padding-top: 2.5rem; padding-bottom: 3rem;">

    <div class="flex-between mb-6">
        <h1 class="page-title">MY PROFILE</h1>
        @if(Auth::user()->rol === 'client')
            <a href="{{ route('cliente.dashboard') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1.5"></i> Back to Dashboard
            </a>
        @else
            <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1.5"></i> Back to Dashboard
            </a>
        @endif
    </div>

    @if(session('status') === 'profile-updated')
        <div class="alert alert-success flex items-center gap-3 mb-6">
            <i class="fas fa-check-circle flex-shrink-0"></i>
            <span>Profile updated successfully.</span>
        </div>
    @endif
    @if(session('status') === 'password-updated')
        <div class="alert alert-success flex items-center gap-3 mb-6">
            <i class="fas fa-check-circle flex-shrink-0"></i>
            <span>Password updated successfully.</span>
        </div>
    @endif

    {{-- Two-column layout: account info (left) + forms (right) --}}
    <div style="display:grid; grid-template-columns: 260px 1fr; gap: 1.5rem; align-items: start;">

        {{-- Sidebar: Account summary --}}
        <div style="display:flex; flex-direction:column; gap:1rem;">

            {{-- Avatar / Initials --}}
            <div style="background: var(--color-bg-secondary); border:1px solid var(--border-subtle);
                        border-radius:14px; padding:1.75rem 1.25rem; text-align:center;">
                <div style="width:72px; height:72px; border-radius:50%; background: var(--color-accent-bright);
                            display:flex; align-items:center; justify-content:center;
                            margin:0 auto 1rem; font-size:1.6rem; font-weight:900; color:#fff;">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}{{ strtoupper(substr(Auth::user()->apellidos ?? '', 0, 1)) }}
                </div>
                <p style="font-weight:800; font-size:1rem; margin:0 0 0.2rem; color: var(--color-text-primary);">
                    {{ Auth::user()->name }} {{ Auth::user()->apellidos }}
                </p>
                <p style="color: var(--color-text-secondary); font-size:0.8rem; margin:0 0 0.75rem;">
                    {{ Auth::user()->email }}
                </p>
                <span style="background: var(--color-accent-bright)22; color: var(--color-accent-bright);
                             border:1px solid var(--color-accent-bright)44; padding:2px 12px;
                             border-radius:20px; font-size:0.7rem; font-weight:800; letter-spacing:1px;
                             text-transform:uppercase;">
                    {{ strtoupper(Auth::user()->rol) }}
                </span>
            </div>

            {{-- Basic stats --}}
            <div style="background: var(--color-bg-secondary); border:1px solid var(--border-subtle);
                        border-radius:14px; padding:1.25rem;">
                <p style="color: var(--color-text-secondary); font-size:0.7rem; font-weight:800;
                          letter-spacing:1px; text-transform:uppercase; margin:0 0 0.875rem;">
                    STATISTICS
                </p>
                @php
                    $totalReservas = Auth::user()->reservas()->count();
                    $memberSince   = Auth::user()->created_at->locale('en')->isoFormat('MMMM YYYY');
                @endphp
                <div style="display:flex; flex-direction:column; gap:0.6rem;">
                    <div style="display:flex; justify-content:space-between; font-size:0.82rem;">
                        <span style="color: var(--color-text-secondary);">Total bookings</span>
                        <span style="font-weight:800; color: var(--color-accent-bright);">{{ $totalReservas }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:0.82rem;">
                        <span style="color: var(--color-text-secondary);">Member since</span>
                        <span style="font-weight:600; color: var(--color-text-primary); font-size:0.75rem;">{{ $memberSince }}</span>
                    </div>
                    @if(Auth::user()->telefono)
                    <div style="display:flex; justify-content:space-between; font-size:0.82rem;">
                        <span style="color: var(--color-text-secondary);">Phone</span>
                        <span style="font-weight:600;">{{ Auth::user()->telefono }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Quick navigation --}}
            <div style="background: var(--color-bg-secondary); border:1px solid var(--border-subtle);
                        border-radius:14px; padding:1.25rem;">
                <p style="color: var(--color-text-secondary); font-size:0.7rem; font-weight:800;
                          letter-spacing:1px; text-transform:uppercase; margin:0 0 0.875rem;">
                    QUICK ACTIONS
                </p>
                <div style="display:flex; flex-direction:column; gap:0.5rem;">
                    <a href="{{ route('reservas.mis') }}"
                       style="display:flex; align-items:center; gap:0.6rem; color: var(--color-text-secondary);
                              text-decoration:none; font-size:0.85rem; padding:0.4rem 0;
                              border-bottom:1px solid var(--border-subtle); transition:color 0.15s;"
                       onmouseover="this.style.color='var(--color-text-primary)'"
                       onmouseout="this.style.color='var(--color-text-secondary)'">
                        <i class="fas fa-ticket-alt" style="width:16px; color: var(--color-accent-bright);"></i>
                        My Bookings
                    </a>
                    <a href="{{ route('peliculas.index') }}"
                       style="display:flex; align-items:center; gap:0.6rem; color: var(--color-text-secondary);
                              text-decoration:none; font-size:0.85rem; padding:0.4rem 0;
                              transition:color 0.15s;"
                       onmouseover="this.style.color='var(--color-text-primary)'"
                       onmouseout="this.style.color='var(--color-text-secondary)'">
                        <i class="fas fa-film" style="width:16px; color: var(--color-accent-bright);"></i>
                        Now Showing
                    </a>
                </div>
            </div>
        </div>

        {{-- Main forms --}}
        <div style="display:flex; flex-direction:column; gap:1.25rem;">

            {{-- Personal information --}}
            <div style="background: var(--color-bg-secondary); border:1px solid var(--border-subtle);
                        border-radius:14px; padding:1.75rem;">

                <h2 style="font-size:0.75rem; font-weight:900; letter-spacing:0.18em;
                           text-transform:uppercase; color: var(--color-text-secondary); margin:0 0 1.25rem;">
                    <i class="fas fa-user mr-2" style="color: var(--color-accent-bright);"></i>
                    PERSONAL INFORMATION
                </h2>

                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PATCH')

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" for="name">Name <span style="color:var(--color-accent-bright)">*</span></label>
                            <input type="text" id="name" name="name" class="form-input"
                                   value="{{ old('name', $user->name) }}" required autocomplete="name">
                            @error('name')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" for="apellidos">Last Name</label>
                            <input type="text" id="apellidos" name="apellidos" class="form-input"
                                   value="{{ old('apellidos', $user->apellidos) }}" autocomplete="family-name">
                            @error('apellidos')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email <span style="color:var(--color-accent-bright)">*</span></label>
                        <input type="email" id="email" name="email" class="form-input"
                               value="{{ old('email', $user->email) }}" required autocomplete="username">
                        @error('email')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="telefono">Phone</label>
                        <input type="tel" id="telefono" name="telefono" class="form-input"
                               value="{{ old('telefono', $user->telefono) }}" autocomplete="tel"
                               placeholder="+34 6XX XXX XXX">
                        @error('telefono')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                </form>
            </div>

            {{-- Change password --}}
            <div style="background: var(--color-bg-secondary); border:1px solid var(--border-subtle);
                        border-radius:14px; padding:1.75rem;">

                <h2 style="font-size:0.75rem; font-weight:900; letter-spacing:0.18em;
                           text-transform:uppercase; color: var(--color-text-secondary); margin:0 0 1.25rem;">
                    <i class="fas fa-lock mr-2" style="color: var(--color-accent-bright);"></i>
                    CHANGE PASSWORD
                </h2>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-input"
                               autocomplete="current-password">
                        @error('current_password', 'updatePassword')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" for="password">New Password</label>
                            <input type="password" id="password" name="password" class="form-input"
                                   autocomplete="new-password">
                            @error('password', 'updatePassword')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" for="password_confirmation">Confirm New Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="form-input" autocomplete="new-password">
                        </div>
                    </div>
                    <div style="margin-top:1rem;">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-key mr-2"></i>Update Password
                        </button>
                    </div>
                </form>
            </div>

            {{-- Danger zone: delete account --}}
            <div style="background: var(--color-bg-secondary); border:1px solid rgba(239,68,68,0.3);
                        border-radius:14px; padding:1.75rem;">

                <h2 style="font-size:0.75rem; font-weight:900; letter-spacing:0.18em;
                           text-transform:uppercase; color: #ef4444; margin:0 0 0.75rem;">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    DANGER ZONE
                </h2>
                <p style="color: var(--color-text-secondary); font-size:0.82rem; margin:0 0 1.25rem; line-height:1.6;">
                    Once your account is deleted, all your data will be permanently lost.
                </p>

                <button type="button"
                        onclick="document.getElementById('deleteModal').style.display='flex'"
                        style="background:transparent; border:1px solid #ef4444; color:#ef4444;
                               border-radius:8px; padding:0.55rem 1.25rem; font-size:0.82rem;
                               font-weight:700; cursor:pointer; transition:background 0.15s, color 0.15s;"
                        onmouseover="this.style.background='#ef4444';this.style.color='#fff'"
                        onmouseout="this.style.background='transparent';this.style.color='#ef4444'">
                    <i class="fas fa-trash mr-1.5"></i>Delete Account
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Delete confirmation modal --}}
<div id="deleteModal"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75);
            z-index:9999; align-items:center; justify-content:center;">
    <div style="background: var(--color-bg-primary); border:1px solid var(--border-subtle);
                border-radius:16px; padding:2rem; max-width:440px; width:90%; margin:1rem;">

        <h3 style="font-size:1rem; font-weight:900; color:#ef4444; margin:0 0 0.75rem;">
            <i class="fas fa-exclamation-triangle mr-2"></i>Delete Account
        </h3>
        <p style="color: var(--color-text-secondary); font-size:0.85rem; margin:0 0 1.25rem; line-height:1.6;">
            Enter your password to confirm you want to permanently delete your account.
        </p>

        <form method="POST" action="{{ route('profile.destroy') }}">
            @csrf
            @method('DELETE')

            <div class="form-group">
                <label class="form-label" for="delete_password">Password</label>
                <input type="password" id="delete_password" name="password" class="form-input"
                       placeholder="Your current password">
                @error('password', 'userDeletion')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
                <button type="button"
                        onclick="document.getElementById('deleteModal').style.display='none'"
                        class="btn btn-secondary">
                    Cancel
                </button>
                <button type="submit"
                        style="background:#ef4444; color:#fff; border:none; border-radius:8px;
                               padding:0.6rem 1.25rem; font-weight:700; font-size:0.85rem; cursor:pointer;">
                    <i class="fas fa-trash mr-1.5"></i>Delete Account
                </button>
            </div>
        </form>
    </div>
</div>

@if($errors->userDeletion->isNotEmpty())
<script>document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('deleteModal').style.display = 'flex';
});</script>
@endif
@endsection
