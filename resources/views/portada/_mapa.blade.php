{{--
    Mapa de butacas ilustrado (no es el mapa real de la compra: es la imagen de «Cómo se compra»).
    Los símbolos #bt-* se definen una vez en _compra.blade.php. $tuya: ¿la butaca D5 está elegida?
--}}
@props(['tuya' => false])
@php
    $filas = ['A', 'B', 'C', 'D', 'E', 'F'];
    $vendidas = ['A3', 'A4', 'B7', 'B8', 'C2', 'C9', 'D8', 'D9', 'E4', 'E5', 'E6', 'F1', 'F10'];
    $retenidas = ['C5', 'E8'];
@endphp
<svg viewBox="0 0 440 300" {{ $attributes->merge(['class' => 'mapa-svg']) }} aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="haz-pantalla{{ $tuya ? '-b' : '' }}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#F2EEE6" stop-opacity=".22"/>
            <stop offset="1" stop-color="#F2EEE6" stop-opacity="0"/>
        </linearGradient>
    </defs>
    <path class="mapa-luz" d="M40 36 L400 36 L440 150 L0 150 Z" fill="url(#haz-pantalla{{ $tuya ? '-b' : '' }})"/>
    <path class="mapa-pantalla" d="M36 34Q220 6 404 34" fill="none" stroke="#F2EEE6" stroke-width="4" stroke-linecap="round"/>
    @foreach ($filas as $i => $fila)
        <g class="mapa-fila">
            @for ($n = 1; $n <= 10; $n++)
                @php
                    $id = $fila.$n;
                    $x = 36 + ($n - 1) * 36 + ($n > 5 ? 20 : 0);
                    $y = 70 + $i * 37;
                    $simbolo = in_array($id, $vendidas) ? 'vendida' : (in_array($id, $retenidas) ? 'retenida' : 'libre');
                    $esTuya = $tuya && $id === 'D5';
                @endphp
                <g class="mapa-butaca{{ $esTuya ? ' mapa-butaca--tuya' : '' }}">
                    <use href="#bt-{{ $esTuya ? 'tuya' : $simbolo }}" x="{{ $x }}" y="{{ $y }}" width="28" height="28"/>
                </g>
            @endfor
        </g>
    @endforeach
</svg>
