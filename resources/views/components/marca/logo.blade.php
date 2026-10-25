{{--
    Logotipo: símbolo + nombre. El nombre sale de config('app.name'), así que cambiar
    de nombre es cambiar APP_NAME (y los SVG de public/img/marca). El texto real lo leen
    los lectores de pantalla; el símbolo es decorativo.
    animado: pinta el símbolo con Rive (solo portada y /marca).
--}}
@props(['tam' => 'md', 'animado' => false])
<span {{ $attributes->merge(['class' => 'marca-logo '.($tam === 'lg' ? 'marca-logo--lg' : 'marca-logo--md')]) }}>
    @if ($animado)
        {{-- Sesión 11: el símbolo se anima con Rive (rive/logo). El SVG es el estado de reposo y la reserva si el runtime no carga. --}}
        <span class="rive-logo" data-rive="logo" data-rive-src="{{ asset('rive/logo.riv') }}">
            <x-marca.simbolo class="marca-logo-simbolo" />
            <canvas class="rive-logo-lienzo" aria-hidden="true" tabindex="-1"></canvas>
        </span>
    @else
        <x-marca.simbolo class="marca-logo-simbolo" />
    @endif
    <span class="marca-logo-nombre">{{ config('app.name') }}</span>
</span>
