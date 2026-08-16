@extends('layout')

@section('title', 'Edit Screening Room')

@section('content')
<div class="container" style="max-width: 600px; padding-top: 3rem;">
    <div style="background: var(--color-bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem;">
        <h1 class="page-title" style="margin-bottom: 1.5rem;">EDIT SCREENING ROOM</h1>

        @if ($errors->any())
            <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                <ul style="margin: 0; padding-left: 1.2rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('salas.update', $sala->id) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="nombre" class="form-label">Screening Room Name</label>
                <input type="text" id="nombre" name="nombre" class="form-input"
                       value="{{ old('nombre', $sala->nombre) }}" required>
                @error('nombre')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="capacidad" class="form-label">Capacity (Seats)</label>
                <input type="number" id="capacidad" name="capacidad" class="form-input"
                       value="{{ old('capacidad', $sala->capacidad) }}" required min="1">
                @error('capacidad')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="disposicion_butacas" class="form-label">Seat Layout</label>
                <textarea id="disposicion_butacas" name="disposicion_butacas" class="form-textarea"
                          required style="height: 100px;">{{ old('disposicion_butacas', $sala->disposicion_butacas) }}</textarea>
                @error('disposicion_butacas')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="fk_cine_id" class="form-label">Cinema (optional)</label>
                <select id="fk_cine_id" name="fk_cine_id" class="form-select">
                    <option value="">-- No cinema assigned --</option>
                    @foreach($cines as $cine)
                        <option value="{{ $cine->id }}" {{ old('fk_cine_id', $sala->fk_cine_id) == $cine->id ? 'selected' : '' }}>
                            {{ $cine->nombre }} ({{ $cine->ciudad }})
                        </option>
                    @endforeach
                </select>
                @error('fk_cine_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Update Screening Room</button>
                <a href="{{ route('salas.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </div>
</div>
@endsection
