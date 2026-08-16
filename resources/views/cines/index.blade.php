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
                <i class="fas fa-film" style="color:rgba(255,255,255,0.05);font-size:5rem;"></i>
            </div>
            <div class="card-cine-overlay" style="z-index:2;">
                <h3 class="card-cine-title">{{ $cine->nombre }}</h3>
                <p class="card-cine-ciudad">{{ $cine->ciudad }}, {{ $cine->provincia }}</p>
                <div style="display:flex;gap:1rem;margin-top:0.5rem;">
                    <span style="color:rgba(255,255,255,0.5);font-size:0.8rem;"><i class="fas fa-door-open" style="margin-right:4px;"></i>{{ $cine->salas_count }} {{ $cine->salas_count == 1 ? 'screen' : 'screens' }}</span>
                    <span style="color:rgba(255,255,255,0.5);font-size:0.8rem;"><i class="fas fa-map-marker-alt" style="margin-right:4px;"></i>{{ Str::limit($cine->direccion_completa, 28) }}</span>
                </div>

            </div>
        </div>
        @empty
        <div style="grid-column:1/-1;text-align:center;padding:4rem;color:var(--color-text-secondary);">
            <i class="fas fa-building" style="font-size:3rem;opacity:0.2;display:block;margin-bottom:1rem;"></i>
            No cinemas registered yet.
        </div>
        @endforelse
    </div>

</div>
@endsection
