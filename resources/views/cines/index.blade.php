@extends('layout')

@section('title', 'Our Cinemas')

@section('content')
<div class="container" style="padding-top:2.5rem;padding-bottom:4rem;">

    <div class="flex-between mb-2" style="align-items:flex-end;">
        <div>
            <h1 class="page-title">OUR CINEMAS</h1>
            <p style="color:var(--color-text-secondary);font-size:0.9rem;margin-top:0.25rem;">{{ $cines->count() }} {{ $cines->count() == 1 ? 'cinema' : 'cinemas' }} nationwide</p>
        </div>

    </div>

    @if(session('success'))
        <div class="alert alert-success mb-2">{{ session('success') }}</div>
    @endif

    <div class="cines-grid">
        @forelse($cines as $cine)
        <div class="card-cine">
            <a href="{{ route('cines.show', $cine) }}" style="display:block;position:absolute;inset:0;z-index:1;text-decoration:none;"></a>
            <div style="width:100%;height:100%;background:linear-gradient(135deg,#1a1a2e,#16213e,#0f3460);display:flex;align-items:center;justify-content:center;">
                <x-icon name="film" class="h-4 w-4 [color:rgba(255,255,255,0.05)]" />
            </div>
            <div class="card-cine-overlay" style="z-index:2;">
                <h3 class="card-cine-title">{{ $cine->nombre }}</h3>
                <p class="card-cine-ciudad">{{ $cine->ciudad }}, {{ $cine->provincia }}</p>
                <div style="display:flex;gap:1rem;margin-top:0.5rem;">
                    <span style="color:rgba(255,255,255,0.5);font-size:0.8rem;"><x-icon name="door" class="h-4 w-4 mr-1" />{{ $cine->salas_count }} {{ $cine->salas_count == 1 ? 'screen' : 'screens' }}</span>
                    <span style="color:rgba(255,255,255,0.5);font-size:0.8rem;"><x-icon name="map-pin" class="h-4 w-4 mr-1" />{{ Str::limit($cine->direccion_completa, 28) }}</span>
                </div>

            </div>
        </div>
        @empty
        <div style="grid-column:1/-1;text-align:center;padding:4rem;color:var(--color-text-secondary);">
            <x-icon name="building" class="h-12 w-12 opacity-20 block mb-4" />
            No cinemas registered yet.
        </div>
        @endforelse
    </div>

</div>
@endsection
