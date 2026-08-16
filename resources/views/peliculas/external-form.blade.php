@extends('layout')

@section('title', $mode === 'edit' ? 'Edit External API' : 'Create External API')

@section('content')
<div class="container" style="max-width:860px;padding-top:2.25rem;padding-bottom:4rem;">
    <a href="{{ route('peliculas.external.index') }}" style="display:inline-flex;align-items:center;gap:0.5rem;color:var(--color-accent-bright);text-decoration:none;font-weight:600;margin-bottom:1.5rem;">&larr; Back to API Listings</a>

    <div style="background:var(--color-bg-secondary);border:1px solid var(--border-subtle);border-radius:12px;padding:1.5rem;">
        <h1 class="page-title" style="font-size:1.5rem;margin-bottom:1rem;">{{ $mode === 'edit' ? 'EDIT API MOVIE' : 'NEW API MOVIE' }}</h1>

        @if(session('error'))
            <div class="alert" style="border:1px solid #dc2626;color:#dc2626;background:rgba(220,38,38,0.08);margin-bottom:1rem;">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ $mode === 'edit' ? route('admin.peliculas.external.update', $movie['id']) : route('admin.peliculas.external.store') }}">
            @csrf
            @if($mode === 'edit')
                @method('PUT')
            @endif

            <div class="form-group">
                <label for="title" class="form-label">Title *</label>
                <input id="title" name="title" class="form-input" required value="{{ old('title', $movie['title'] ?? '') }}">
                @error('title')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-input" rows="4">{{ old('description', $movie['description'] ?? '') }}</textarea>
                @error('description')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;">
                <div class="form-group">
                    <label for="year" class="form-label">Year</label>
                    <input id="year" name="year" type="number" class="form-input" min="1900" max="2100" value="{{ old('year', $movie['year'] ?? '') }}">
                    @error('year')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label for="genre" class="form-label">Genre</label>
                    <input id="genre" name="genre" class="form-input" value="{{ old('genre', $movie['genre'] ?? '') }}">
                    @error('genre')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label for="stars" class="form-label">Rating (0-5)</label>
                    <input id="stars" name="stars" type="number" step="0.1" min="0" max="5" class="form-input" value="{{ old('stars', $movie['stars'] ?? '') }}">
                    @error('stars')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-group">
                <label for="image_url" class="form-label">Image URL</label>
                <input id="image_url" name="image_url" type="url" class="form-input" value="{{ old('image_url', $movie['image_url'] ?? '') }}">
                @error('image_url')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1rem;">
                <a href="{{ route('peliculas.external.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">{{ $mode === 'edit' ? 'Save API Changes' : 'Create in API' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
