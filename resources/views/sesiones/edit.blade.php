@extends('layout')

@section('title', 'Edit Showtime')

@section('content')
<div class="container max-w-2xl py-10">

    <a href="{{ route('sesiones.index') }}"
       class="inline-flex items-center gap-2 text-[color:var(--color-text-secondary)] hover:text-[color:var(--color-accent-bright)] text-sm font-semibold transition-colors mb-6">
        <i class="fas fa-arrow-left text-xs"></i> Back to showtimes
    </a>

    <div class="bg-cinema-surface border border-white/10 rounded-2xl p-8">
        <h1 class="page-title mb-6">EDIT SHOWTIME <span class="text-cinema-accent">#{{ $sesion->id }}</span></h1>

        @if ($errors->any())
            <div class="alert alert-error flex items-start gap-3 mb-6">
                <i class="fas fa-exclamation-circle flex-shrink-0 mt-0.5"></i>
                <ul class="list-disc list-inside space-y-0.5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('sesiones.update', $sesion->id) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="fk_sala_id" class="form-label">Screening Room</label>
                <select id="fk_sala_id" name="fk_sala_id" class="form-select" required>
                    <option value="">— Select a screening room —</option>
                    @foreach($salas as $sala)
                        <option value="{{ $sala->id }}" {{ old('fk_sala_id', $sesion->fk_sala_id) == $sala->id ? 'selected' : '' }}>
                            {{ $sala->nombre }} (Capacity: {{ $sala->capacidad }})
                        </option>
                    @endforeach
                </select>
                @error('fk_sala_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="fk_pelicula_id" class="form-label">Movie</label>
                <select id="fk_pelicula_id" name="fk_pelicula_id" class="form-select" required>
                    <option value="">— Select a movie —</option>
                    @foreach($peliculas as $pelicula)
                        <option value="{{ $pelicula->id }}" {{ old('fk_pelicula_id', $sesion->fk_pelicula_id) == $pelicula->id ? 'selected' : '' }}>
                            {{ $pelicula->titulo }} ({{ $pelicula->duracion_min }} min)
                        </option>
                    @endforeach
                </select>
                @error('fk_pelicula_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="fecha_hora" class="form-label">Date and Time</label>
                    <input type="datetime-local" id="fecha_hora" name="fecha_hora" class="form-input"
                           value="{{ old('fecha_hora', $sesion->fecha_hora->format('Y-m-d\TH:i')) }}" required>
                    @error('fecha_hora')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="preu_base" class="form-label">Base Price (€)</label>
                    <input type="number" id="preu_base" name="preu_base" class="form-input"
                           value="{{ old('preu_base', $sesion->preu_base) }}" step="0.01" min="0" required>
                    @error('preu_base')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1.5"></i> Update Showtime
                </button>
                <a href="{{ route('sesiones.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
