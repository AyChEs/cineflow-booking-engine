@extends('layout')

@section('title', 'New Cinema')

@section('content')
<div class="container py-12" style="max-width: 640px;">
    <div class="bg-cinema-surface border border-white/10 rounded-2xl p-8">

        <h1 class="page-title mb-6">NEW CINEMA</h1>

        @if ($errors->any())
            <div class="alert alert-error flex items-start gap-3 mb-6">
                <i class="fas fa-exclamation-circle flex-shrink-0 mt-0.5"></i>
                <ul class="list-none m-0 p-0 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('cines.store') }}">
            @csrf

            <div class="form-group">
                <label for="nombre" class="form-label">
                    Cinema Name <span class="text-cinema-accent">*</span>
                </label>
                <input type="text" id="nombre" name="nombre" class="form-input"
                       value="{{ old('nombre') }}" required
                       placeholder="e.g. CineFlow Barcelona">
                @error('nombre')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="direccion_completa" class="form-label">
                    Full Address <span class="text-cinema-accent">*</span>
                </label>
                <input type="text" id="direccion_completa" name="direccion_completa" class="form-input"
                       value="{{ old('direccion_completa') }}" required
                       placeholder="e.g. Carrer de Mallorca, 123">
                @error('direccion_completa')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="ciudad" class="form-label">
                        City <span class="text-cinema-accent">*</span>
                    </label>
                    <input type="text" id="ciudad" name="ciudad" class="form-input"
                           value="{{ old('ciudad') }}" required
                           placeholder="e.g. Barcelona">
                    @error('ciudad')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="provincia" class="form-label">
                        Province <span class="text-cinema-accent">*</span>
                    </label>
                    <input type="text" id="provincia" name="provincia" class="form-input"
                           value="{{ old('provincia') }}" required
                           placeholder="e.g. Barcelona">
                    @error('provincia')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Save Cinema
                </button>
                <a href="{{ route('cines.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection