@extends('layout')

@section('title', 'Nueva sala')

@section('content')
<div class="container max-w-2xl py-10">

    <a href="{{ route('salas.index') }}"
       class="inline-flex items-center gap-2 text-[color:var(--color-text-secondary)] hover:text-[color:var(--color-accent-bright)] text-sm font-semibold transition-colors mb-6">
        <x-icon name="arrow-left" class="h-3 w-3" /> Volver a las salas
    </a>

    <div class="bg-sala-900 border border-white/10 rounded-2xl p-8">
        <h1 class="page-title mb-6">Nueva sala</h1>

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

        <form method="POST" action="{{ route('salas.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="nombre" class="form-label">Nombre de la sala</label>
                    <input type="text" id="nombre" name="nombre" class="form-input"
                           value="{{ old('nombre') }}" required>
                    @error('nombre')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="capacidad" class="form-label">Capacidad (butacas)</label>
                    <input type="number" id="capacidad" name="capacidad" class="form-input"
                           value="{{ old('capacidad') }}" required min="1">
                    @error('capacidad')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-group">
                <label for="disposicion_butacas" class="form-label">Disposición de butacas</label>
                <textarea id="disposicion_butacas" name="disposicion_butacas"
                          class="form-textarea"
                          required>{{ old('disposicion_butacas') }}</textarea>
                @error('disposicion_butacas')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="fk_cine_id" class="form-label">
                    Cine
                    <span class="text-texto-2 font-normal normal-case tracking-normal text-xs ml-1">(optional)</span>
                </label>
                <select id="fk_cine_id" name="fk_cine_id" class="form-select">
                    <option value="">— No cinema assigned —</option>
                    @foreach($cines as $cine)
                        <option value="{{ $cine->id }}" {{ old('fk_cine_id') == $cine->id ? 'selected' : '' }}>
                            {{ $cine->nombre }} ({{ $cine->ciudad }})
                        </option>
                    @endforeach
                </select>
                @error('fk_cine_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn btn-primary">
                    <x-icon name="plus" class="h-4 w-4 mr-1.5" /> Guardar la sala
                </button>
                <a href="{{ route('salas.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
