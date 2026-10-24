{{-- Estado de una reserva con icono y texto (nunca solo color). --}}
@props(['estat'])
@php
    [$texto, $icono, $clase] = match ($estat) {
        'pagat'    => ['Pagada', 'check-circle', 'border-ok/50 text-ok'],
        'pendent'  => ['Pendiente', 'clock', 'border-sala-700 text-texto'],
        'cancelat' => ['Cancelada', 'x-circle', 'border-error/50 text-error'],
        default    => [ucfirst((string) $estat), 'info', 'border-sala-700 text-texto-2'],
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-sm font-semibold {$clase}"]) }}>
    <x-icon :name="$icono" class="h-4 w-4" />{{ $texto }}
</span>
