@extends('layout')

@section('title', 'Editar la película')

@section('content')
<div class="container" style="max-width: 700px; padding-top: 3rem;">
    <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem;">
        <h1 class="page-title" style="margin-bottom: 1.5rem;">Editar la película</h1>

        @if ($errors->any())
            <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                <ul style="margin: 0; padding-left: 1.2rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('peliculas.update', $pelicula->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="titulo" class="form-label">Título <span style="color: var(--color-accent-bright);">*</span></label>
                <input type="text" id="titulo" name="titulo" class="form-input"
                       value="{{ old('titulo', $pelicula->titulo) }}" required>
                @error('titulo')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="sinopsis" class="form-label">Sinopsis</label>
                <textarea id="sinopsis" name="sinopsis" class="form-textarea" rows="4">{{ old('sinopsis', $pelicula->sinopsis) }}</textarea>
                @error('sinopsis')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="duracion_min" class="form-label">Duración (minutos) <span style="color: var(--color-accent-bright);">*</span></label>
                <input type="number" id="duracion_min" name="duracion_min" class="form-input"
                       value="{{ old('duracion_min', $pelicula->duracion_min) }}" required min="1">
                @error('duracion_min')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="classificacio_edad" class="form-label">Clasificación por edad</label>
                <input type="text" id="classificacio_edad" name="classificacio_edad" class="form-input"
                       value="{{ old('classificacio_edad', $pelicula->classificacio_edad) }}"
                       placeholder="Por ejemplo, TP, +7, +12, +16, +18">
                @error('classificacio_edad')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="trailer_url" class="form-label">Trailer URL</label>
                <input type="url" id="trailer_url" name="trailer_url" class="form-input"
                       value="{{ old('trailer_url', $pelicula->trailer_url) }}" placeholder="https://...">
                @error('trailer_url')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="poster" class="form-label">Póster</label>
                <div style="margin-bottom: 1rem;">
                    <img src="{{ $pelicula->poster_url }}" alt="Poster for {{ $pelicula->titulo }}" style="width: 140px; aspect-ratio: 2/3; object-fit: cover; border-radius: 10px; border: 1px solid var(--border-subtle); display: block;">
                </div>
                <input type="file" id="poster" name="poster" class="form-input" accept="image/png,image/jpeg,image/webp,image/avif">
                <p style="color: var(--color-text-secondary); font-size: 0.8rem; margin-top: 0.5rem;">Si eliges una imagen nueva, sustituye a la actual.</p>
                @error('poster')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Genres</label>
                <div style="border: 1px solid var(--border-subtle); border-radius: 8px; padding: 1rem; max-height: 180px; overflow-y: auto; background: var(--color-bg-primary);">
                    @forelse($categorias as $categoria)
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.25rem 0; color: var(--color-text-primary);">
                            <input type="checkbox" name="categorias[]" value="{{ $categoria->id }}"
                                   {{ in_array($categoria->id, old('categorias', $pelicula->categorias->pluck('id')->toArray())) ? 'checked' : '' }}
                                   style="accent-color: var(--color-accent-bright);">
                            {{ $categoria->nombre }}
                        </label>
                    @empty
                        <p style="color: var(--color-text-secondary); margin: 0;">No genres available.</p>
                    @endforelse
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Guardar la película</button>
                <a href="{{ route('peliculas.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
