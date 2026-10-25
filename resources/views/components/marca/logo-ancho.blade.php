{{--
    Logotipo a todo el ancho (pie): símbolo + nombre. El símbolo es el de x-marca.simbolo y no depende del
    texto; el nombre sale de config('app.name') y textLength lo ajusta al ancho que queda.
--}}
@props(['decorativo' => false])
<svg viewBox="0 0 1000 160" {{ $attributes->merge(['class' => 'logo-ancho']) }} @if ($decorativo) aria-hidden="true" focusable="false" @else role="img" aria-label="{{ config('app.name') }}" @endif>
    <path class="logo-ancho-simbolo" transform="translate(-15.4 -18.4) scale(3.07)" fill-rule="evenodd" d="M28 6H36A13 13 0 0 1 49 19V19.5A4.5 4.5 0 0 0 49 28.5V37A3 3 0 0 1 46 40H18A3 3 0 0 1 15 37V28.5A4.5 4.5 0 0 0 15 19.5V19A13 13 0 0 1 28 6ZM20.20 24a1.8 1.8 0 1 0 3.6 0a1.8 1.8 0 1 0 -3.6 0ZM26.87 24a1.8 1.8 0 1 0 3.6 0a1.8 1.8 0 1 0 -3.6 0ZM33.53 24a1.8 1.8 0 1 0 3.6 0a1.8 1.8 0 1 0 -3.6 0ZM40.20 24a1.8 1.8 0 1 0 3.6 0a1.8 1.8 0 1 0 -3.6 0ZM5 29A3 3 0 0 1 11 29V44H53V29A3 3 0 0 1 59 29V53A5 5 0 0 1 54 58H10A5 5 0 0 1 5 53Z"/>
    <text class="logo-ancho-nombre" x="196" y="158" textLength="800" lengthAdjust="spacing" font-size="200">{{ config('app.name') }}</text>
</svg>
