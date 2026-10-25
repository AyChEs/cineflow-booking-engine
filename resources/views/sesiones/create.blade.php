@extends('layout')

@section('title', 'Nueva sesión')

@section('content')
<div class="container max-w-2xl py-10">

    <a href="{{ route('sesiones.index') }}"
       class="inline-flex items-center gap-2 text-[color:var(--color-text-secondary)] hover:text-[color:var(--color-accent-bright)] text-sm font-semibold transition-colors mb-6">
        <x-icon name="arrow-left" class="h-3 w-3" /> Volver a las sesiones
    </a>

    <div class="bg-sala-900 border border-white/10 rounded-2xl p-8">
        <h1 class="page-title mb-6">Nueva sesión</h1>

        @if ($errors->any())
            <div class="alert alert-error flex items-start gap-3 mb-6">
                <x-icon name="alert" class="h-4 w-4 flex-shrink-0 mt-0.5" />
                <ul class="list-disc list-inside space-y-0.5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('sesiones.store') }}">
            @csrf

            <div class="form-group">
                <label for="fk_sala_id" class="form-label">Sala</label>
                <select id="fk_sala_id" name="fk_sala_id" class="form-select" required>
                    <option value="">Elige una sala</option>
                    @foreach($salas as $sala)
                        <option value="{{ $sala->id }}" {{ old('fk_sala_id') == $sala->id ? 'selected' : '' }}>
                            {{ $sala->nombre }} (Capacity: {{ $sala->capacidad }})
                        </option>
                    @endforeach
                </select>
                @error('fk_sala_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="fk_pelicula_id" class="form-label">Película</label>
                <select id="fk_pelicula_id" name="fk_pelicula_id" class="form-select" required>
                    <option value="">Elige una película</option>
                    @foreach($peliculas as $pelicula)
                        <option value="{{ $pelicula->id }}" {{ old('fk_pelicula_id') == $pelicula->id ? 'selected' : '' }}>
                            {{ $pelicula->titulo }} ({{ $pelicula->duracion_min }} min)
                        </option>
                    @endforeach
                </select>
                @error('fk_pelicula_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="fecha_hora" class="form-label">Fecha y hora</label>
                    <input type="datetime-local" id="fecha_hora" name="fecha_hora" class="form-input"
                           value="{{ old('fecha_hora') }}" required>
                    @error('fecha_hora')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="preu_base" class="form-label">Precio base (€)</label>
                    <input type="number" id="preu_base" name="preu_base" class="form-input"
                           value="{{ old('preu_base') }}" step="0.01" min="0" placeholder="0.00" required>
                    @error('preu_base')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn btn-primary">
                    <x-icon name="plus" class="h-4 w-4 mr-1.5" /> Crear la sesión
                </button>
                <a href="{{ route('sesiones.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
