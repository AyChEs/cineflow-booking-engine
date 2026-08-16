@extends('layout')

@section('title', 'Edit Cinema')

@section('content')
<div class="container" style="max-width: 600px; padding-top: 3rem;">
    <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem;">
        <h1 class="page-title" style="margin-bottom: 1.5rem;">EDIT CINEMA</h1>

        @if ($errors->any())
            <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                <ul style="margin: 0; padding-left: 1.2rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('cines.update', $cine->id) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="nombre" class="form-label">Cinema Name</label>
                <input type="text" id="nombre" name="nombre" class="form-input"
                       value="{{ old('nombre', $cine->nombre) }}" required>
                @error('nombre')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="direccion_completa" class="form-label">Full Address</label>
                <input type="text" id="direccion_completa" name="direccion_completa" class="form-input"
                       value="{{ old('direccion_completa', $cine->direccion_completa) }}" required>
                @error('direccion_completa')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="ciudad" class="form-label">City</label>
                <input type="text" id="ciudad" name="ciudad" class="form-input"
                       value="{{ old('ciudad', $cine->ciudad) }}" required>
                @error('ciudad')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="provincia" class="form-label">Province</label>
                <input type="text" id="provincia" name="provincia" class="form-input"
                       value="{{ old('provincia', $cine->provincia) }}" required>
                @error('provincia')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Update Cinema</button>
                <a href="{{ route('cines.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </div>
</div>
@endsection
