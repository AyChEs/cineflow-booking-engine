@extends('layout')

@section('title', 'Nueva película')

@section('content')
<div class="container py-12" style="max-width: 720px;">
    <div class="bg-sala-900 border border-white/10 rounded-2xl p-8">

        <h1 class="page-title mb-6">Nueva película</h1>

        @if ($errors->any())
            <div class="alert alert-error flex items-start gap-3 mb-6">
                <x-icon name="alert" class="h-4 w-4 flex-shrink-0 mt-0.5" />
                <ul class="list-none m-0 p-0 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- TMDB search widget: searches for a movie by name and
             auto-fills the form fields with its data. --}}
        <div x-data="tmdbSearchWidget()" class="mb-8 p-5 bg-white/[0.03] border border-white/10 rounded-xl">

            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-texto-2 mb-3 flex items-center gap-2">
                <x-icon name="search" class="h-4 w-4 text-carmin-400" />
                Buscar en TMDB <span class="text-texto-2 font-normal normal-case tracking-normal">(opcional, rellena el formulario)</span>
            </h3>

            <div class="relative">
                <input type="text"
                       x-model="query"
                       @input.debounce.400ms="search()"
                       @keydown.escape="results = []"
                       placeholder="Nombre de la película, por ejemplo Gladiator"
                       class="form-input w-full pr-10"
                       autocomplete="off">

                {{-- Spinner mientras carga --}}
                <div x-show="loading" class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                    <x-icon name="loader" class="h-3.5 w-3.5 text-texto-2 animate-spin" />
                </div>
            </div>

            {{-- Dropdown con resultados --}}
            <div x-show="results.length > 0" x-cloak
                 class="mt-2 bg-sala-950 border border-white/10 rounded-xl overflow-hidden shadow-2xl">
                <template x-for="movie in results" :key="movie.id">
                    <button type="button"
                            @click="select(movie)"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left
                                   hover:bg-white/5 transition-colors
                                   border-b border-white/5 last:border-0">
                        <img :src="movie.image_url || '{{ asset('img/sin-poster.svg') }}'"
                             class="w-10 h-14 object-cover rounded flex-shrink-0"
                             :alt="movie.title">
                        <div class="min-w-0">
                            <div class="font-semibold text-sm text-white truncate" x-text="movie.title"></div>
                            <div class="text-xs text-texto-2 mt-0.5"
                                 x-text="(movie.year ?? '?') + ' · ' + (movie.genre || 'No genre')"></div>
                        </div>
                    </button>
                </template>
            </div>

            {{-- Estado seleccionado --}}
            <div x-show="selected" x-cloak
                 class="mt-3 flex items-center gap-2 text-xs text-green-400">
                <x-icon name="check-circle" class="h-4 w-4" />
                <span>Datos cargados de TMDB. Revísalos y ajústalos si hace falta.</span>
            </div>
        </div>

        {{-- Formulario principal --}}
        <form action="{{ route('peliculas.store') }}" method="POST" enctype="multipart/form-data" id="peliculaForm">
            @csrf

            {{-- Campo hidden para URL de poster de TMDB --}}
            <input type="hidden" name="poster_url" id="posterUrlInput" value="{{ old('poster_url') }}">

            <div class="form-group">
                <label for="titulo" class="form-label">Título <span class="text-carmin-400">*</span></label>
                <input type="text" id="titulo" name="titulo" class="form-input"
                       value="{{ old('titulo') }}" required>
                @error('titulo')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label for="sinopsis" class="form-label">Sinopsis</label>
                <textarea id="sinopsis" name="sinopsis" class="form-textarea" rows="4">{{ old('sinopsis') }}</textarea>
                @error('sinopsis')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="duracion_min" class="form-label">Duración (minutos) <span class="text-carmin-400">*</span></label>
                    <input type="number" id="duracion_min" name="duracion_min" class="form-input"
                           value="{{ old('duracion_min') }}" required min="1">
                    @error('duracion_min')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="classificacio_edad" class="form-label">Clasificación por edad</label>
                    <input type="text" id="classificacio_edad" name="classificacio_edad" class="form-input"
                           value="{{ old('classificacio_edad') }}" placeholder="G, PG, PG-12, R-16, R-18">
                    @error('classificacio_edad')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-group">
                <label for="trailer_url" class="form-label">Trailer URL</label>
                <input type="url" id="trailer_url" name="trailer_url" class="form-input"
                       value="{{ old('trailer_url') }}" placeholder="https://...">
                @error('trailer_url')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            {{-- Poster: subida manual o desde TMDB --}}
            <div class="form-group">
                <label for="poster" class="form-label">Póster</label>

                {{-- Preview del poster (visible cuando se selecciona desde TMDB) --}}
                <div id="posterPreviewWrap" class="hidden mb-3 flex items-start gap-4">
                    <img id="posterPreview" src="" alt="Vista previa del póster"
                         class="w-20 h-28 object-cover rounded-lg border border-white/10">
                    <div class="text-xs text-texto-2 pt-1">
                        <p class="font-semibold text-white mb-1">Poster selected from TMDB</p>
                        <p>Sube un archivo para cambiarlo o déjalo para usar el de TMDB.</p>
                        <button type="button" data-quitar-poster-tmdb
                                class="text-carmin-400 hover:opacity-70 mt-2 transition-opacity">
                            <x-icon name="close" class="h-4 w-4 mr-1" />Quitar el póster de TMDB
                        </button>
                    </div>
                </div>

                <input type="file" id="poster" name="poster" class="form-input"
                       accept="image/png,image/jpeg,image/webp,image/avif">
                <p class="text-texto-2 text-xs mt-1">Supported formats: JPG, PNG, WEBP or AVIF. Max 4 MB.</p>
                @error('poster')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Genres</label>
                <div class="border border-white/10 rounded-lg p-4 max-h-44 overflow-y-auto bg-sala-950/50">
                    @forelse($categorias as $categoria)
                        <label class="flex items-center gap-2 cursor-pointer py-1 hover:text-white transition-colors">
                            <input type="checkbox" name="categorias[]" value="{{ $categoria->id }}"
                                   {{ in_array($categoria->id, old('categorias', [])) ? 'checked' : '' }}
                                   class="accent-carmin-600">
                            {{ $categoria->nombre }}
                        </label>
                    @empty
                        <p class="text-texto-2 text-sm m-0">No genres available.</p>
                    @endforelse
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn btn-primary">
                    <x-icon name="save" class="h-4 w-4 mr-2" />Crear la película
                </button>
                <a href="{{ route('peliculas.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script nonce="{{ Vite::cspNonce() }}">
// Alpine.js widget to search for movies on TMDB and auto-fill the form
function tmdbSearchWidget() {
    return {
        query:    '',
        results:  [],
        loading:  false,
        selected: false,

        // Busca en TMDB con debounce (se llama desde @input.debounce.400ms)
        async search() {
            if (this.query.length < 2) { this.results = []; return; }
            this.loading = true;
            try {
                const res = await fetch(
                    `/admin/tmdb/search?q=${encodeURIComponent(this.query)}`,
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
                );
                this.results = await res.json();
            } catch(e) {
                this.results = [];
            } finally {
                this.loading = false;
            }
        },

        // Al seleccionar un resultado, carga el detalle completo y rellena el formulario
        async select(movie) {
            this.results  = [];
            this.loading  = true;
            try {
                const res  = await fetch(`/admin/tmdb/${movie.id}`);
                const data = await res.json();
                if (!data) return;

                // Rellenar campos del formulario
                document.getElementById('titulo').value         = data.title        || '';
                document.getElementById('sinopsis').value       = data.description  || '';
                document.getElementById('trailer_url').value    = '';

                // If TMDB has a runtime in the detail response (can come back null in basic normalization)
                // Only fill it in if the field is empty
                const durField = document.getElementById('duracion_min');
                if (!durField.value && data.runtime) {
                    durField.value = data.runtime;
                }

                // Poster: guardamos la URL y mostramos la preview
                if (data.image_url) {
                    document.getElementById('posterUrlInput').value = data.image_url;
                    document.getElementById('posterPreview').src    = data.image_url;
                    document.getElementById('posterPreviewWrap').classList.remove('hidden');
                    document.getElementById('posterPreviewWrap').classList.add('flex');
                }

                this.query    = data.title;
                this.selected = true;
            } catch(e) {
                // If the detail call fails, keep the basic data from the initial result
                document.getElementById('titulo').value = movie.title || '';
                this.selected = true;
            } finally {
                this.loading = false;
            }
        }
    };
}

// Limpia el poster de TMDB y vuelve a mostrar solo el input de archivo
function clearTmdbPoster() {
    document.getElementById('posterUrlInput').value = '';
    document.getElementById('posterPreview').src    = '';
    document.getElementById('posterPreviewWrap').classList.add('hidden');
    document.getElementById('posterPreviewWrap').classList.remove('flex');
}

// Antes era onclick="…" en el HTML; la CSP no permite manejadores en línea (middleware CabecerasDeSeguridad).
document.querySelectorAll('[data-quitar-poster-tmdb]').forEach((b) => b.addEventListener('click', clearTmdbPoster));
</script>
@endsection
