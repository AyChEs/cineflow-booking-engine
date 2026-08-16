@extends('layout')

@section('title', 'Edit Booking')

@section('content')
<div class="container" style="max-width: 600px; padding-top: 3rem;">
    <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem;">
        <h1 class="page-title" style="margin-bottom: 1.5rem;">EDIT BOOKING #{{ $reserva->id }}</h1>

        @if ($errors->any())
            <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                <ul style="margin: 0; padding-left: 1.2rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('reservas.update', $reserva->id) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="fk_usuario_id" class="form-label">User</label>
                <select id="fk_usuario_id" name="fk_usuario_id" class="form-select" required>
                    <option value="">-- Select a user --</option>
                    @foreach ($usuarios as $user)
                        <option value="{{ $user->id }}" {{ old('fk_usuario_id', $reserva->fk_usuario_id) == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} {{ $user->apellidos }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                @error('fk_usuario_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="fk_sesion_id" class="form-label">Showtime</label>
                <select id="fk_sesion_id" name="fk_sesion_id" class="form-select" required>
                    <option value="">-- Select a showtime --</option>
                    @foreach ($sesiones as $sesion)
                        <option value="{{ $sesion->id }}" {{ old('fk_sesion_id', $reserva->fk_sesion_id) == $sesion->id ? 'selected' : '' }}>
                            #{{ $sesion->id }} – {{ $sesion->pelicula->titulo }} | {{ $sesion->sala->nombre }} | {{ $sesion->fecha_hora->format('d/m/Y H:i') }}
                        </option>
                    @endforeach
                </select>
                @error('fk_sesion_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="butaques_seleccionades" class="form-label">Selected Seats</label>
                <input type="text" id="butaques_seleccionades" name="butaques_seleccionades" class="form-input"
                       placeholder="E.g. A1, A2, B5" value="{{ old('butaques_seleccionades', $reserva->butaques_seleccionades) }}" required>
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-top: 0.25rem;">Comma-separated</p>
                @error('butaques_seleccionades')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="total_pagat" class="form-label">Total Paid (€)</label>
                <input type="number" id="total_pagat" name="total_pagat" class="form-input"
                       step="0.01" min="0" value="{{ old('total_pagat', $reserva->total_pagat) }}" required>
                @error('total_pagat')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="estat" class="form-label">Status</label>
                <select id="estat" name="estat" class="form-select" required>
                    <option value="">-- Select a status --</option>
                    <option value="pendent"  {{ old('estat', $reserva->estat) === 'pendent'  ? 'selected' : '' }}>Pending</option>
                    <option value="pagat"    {{ old('estat', $reserva->estat) === 'pagat'    ? 'selected' : '' }}>Paid</option>
                    <option value="cancelat" {{ old('estat', $reserva->estat) === 'cancelat' ? 'selected' : '' }}>Cancelled</option>
                </select>
                @error('estat')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('reservas.show', $reserva->id) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
