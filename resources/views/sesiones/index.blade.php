@extends('layout')

@section('title', 'Showtimes')

@section('content')
<div class="container" style="padding-top:2.5rem;padding-bottom:4rem;">

    <div style="margin-bottom:2rem;">
        <h1 class="page-title">SHOWTIMES</h1>
        <p style="color:var(--color-text-secondary);font-size:0.9rem;margin-top:0.25rem;">
            All scheduled showtimes
        </p>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom:1.5rem;">{{ session('success') }}</div>
    @endif

    @php
        $grouped = $sesiones->groupBy(fn($s) => $s->fecha_hora->format('Y-m-d'));
    @endphp

    @forelse($grouped as $fecha => $sessionsDelDia)
    <div style="margin-bottom:2.5rem;">
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
            <h2 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:var(--color-text-secondary);margin:0;white-space:nowrap;">
                {{ \Carbon\Carbon::parse($fecha)->locale('en')->isoFormat('dddd D MMMM') }}
            </h2>
            <div style="flex:1;height:1px;background:var(--border-subtle);"></div>
            <span style="font-size:0.72rem;color:var(--color-text-secondary);">{{ $sessionsDelDia->count() }} showtimes</span>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;">
            @foreach($sessionsDelDia as $sesion)
            <div style="background:var(--color-bg-secondary);border:1px solid var(--border-subtle);border-radius:12px;padding:1.25rem;transition:border-color 0.2s;"
                 onmouseover="this.style.borderColor='var(--color-accent-bright)44'" onmouseout="this.style.borderColor='var(--border-subtle)'">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:0.75rem;">
                    <div>
                        <p style="font-weight:800;font-size:0.95rem;margin:0 0 0.2rem;text-transform:uppercase;letter-spacing:0.5px;">
                            {{ $sesion->pelicula->titulo ?? '—' }}
                        </p>
                        <p style="color:var(--color-text-secondary);font-size:0.8rem;margin:0;">
                            <i class="fas fa-map-marker-alt" style="margin-right:4px;"></i>{{ $sesion->sala->cine->nombre ?? '' }} · {{ $sesion->sala->nombre ?? '' }}
                        </p>
                    </div>
                    <span style="background:var(--color-accent-bright);color:#fff;font-weight:900;font-size:1rem;padding:0.4rem 0.75rem;border-radius:8px;white-space:nowrap;margin-left:0.75rem;">
                        {{ $sesion->fecha_hora->format('H:i') }}
                    </span>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid var(--border-subtle)22;">
                    <span style="font-size:0.85rem;font-weight:700;color:var(--color-accent-bright);">
                        €{{ number_format($sesion->preu_base, 2) }}
                    </span>
                    <div style="display:flex;gap:0.5rem;">
                        <a href="{{ route('sesiones.show', $sesion) }}" class="btn btn-secondary" style="padding:0.3rem 0.75rem;font-size:0.75rem;">
                            Info
                        </a>
                        @auth
                            <a href="{{ route('compra.step1') }}?sesion_id={{ $sesion->id }}" class="btn btn-primary" style="padding:0.3rem 0.75rem;font-size:0.75rem;">
                                Buy
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary" style="padding:0.3rem 0.75rem;font-size:0.75rem;">
                                Buy
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div style="text-align:center;padding:6rem 2rem;color:var(--color-text-secondary);">
        <i class="fas fa-calendar-times" style="font-size:3rem;opacity:0.2;display:block;margin-bottom:1rem;"></i>
        No showtimes scheduled at this time.
    </div>
    @endforelse

</div>
@endsection
