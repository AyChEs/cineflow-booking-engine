{{--
    El nombre a todo el ancho (decorativo). Sale de config('app.name'); textLength lo estira al ancho
    del lienzo, así que cualquier nombre encaja. Con `relleno` pinta el texto macizo; sin él, solo el contorno.
    Fuente: Big Shoulders Contorno (misma letra sin contornos solapados).
--}}
@props(['relleno' => false])
<svg viewBox="0 0 1000 214" {{ $attributes->merge(['class' => 'nombre-ancho']) }} aria-hidden="true" focusable="false">
    <text class="{{ $relleno ? 'nombre-ancho-relleno' : 'nombre-ancho-trazo' }}" x="4" y="211" textLength="992" lengthAdjust="spacing" font-size="262">{{ config('app.name') }}</text>
</svg>
