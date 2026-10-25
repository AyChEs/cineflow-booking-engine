{{-- Imagen de marca (docs/MARCA.md, «Imágenes»): AVIF y WebP con varios anchos, con width y height para no mover la página.
     Decorativa por defecto (alt vacío). `prioridad` solo para la imagen LCP: carga ya y con fetchpriority alto. --}}
@props([
    'nombre',
    'sizes' => '100vw',
    'alt' => '',
    'prioridad' => false,
])
@php
    $foto = config('marca.fotos.'.$nombre);
    $srcset = fn (string $ext) => collect($foto['anchos'])
        ->map(fn ($w) => asset("img/marca/fotos/{$nombre}-{$w}.{$ext}")." {$w}w")
        ->join(', ');
    $mayor = max($foto['anchos']);
@endphp
<picture>
    <source type="image/avif" srcset="{{ $srcset('avif') }}" sizes="{{ $sizes }}">
    <img {{ $attributes->merge(['class' => '']) }}
         src="{{ asset("img/marca/fotos/{$nombre}-{$mayor}.webp") }}"
         srcset="{{ $srcset('webp') }}" sizes="{{ $sizes }}"
         alt="{{ $alt }}" width="{{ $foto['ancho'] }}" height="{{ $foto['alto'] }}"
         decoding="async"
         @if ($prioridad) fetchpriority="high" @else loading="lazy" @endif>
</picture>
