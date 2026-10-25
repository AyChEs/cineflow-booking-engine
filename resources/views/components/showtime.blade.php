{{-- Sesión: botón con la hora que lleva al paso 1 de la compra. --}}
@props(['sesion'])
@php $hora = $sesion->fecha_hora->format('G:i'); @endphp
<a href="{{ route('compra.step1', ['sesion_id' => $sesion->id]) }}"
   aria-label="Comprar para las {{ $hora }}, {{ $sesion->sala->nombre }}"
   {{ $attributes->merge(['class' => 'inline-flex min-h-14 min-w-[6.5rem] flex-col justify-center rounded-control border border-sala-700 bg-sala-900 px-4 py-2 no-underline transition-colors hover:border-carmin-400 hover:bg-sala-850']) }}>
    <span class="cifras text-lg font-semibold leading-tight text-texto">{{ $hora }}</span>
    <span class="text-xs text-texto-2">{{ $sesion->sala->nombre }} · <span class="cifras">{{ number_format($sesion->preu_base, 2, ',', '') }} €</span></span>
</a>
