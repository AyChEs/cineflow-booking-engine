{{-- Código QR ilustrativo (no codifica nada): tres marcas de esquina y módulos repartidos de forma determinista. --}}
@php
    $n = 25;
    $reservado = fn ($x, $y) => ($x < 8 && $y < 8) || ($x >= $n - 8 && $y < 8) || ($x < 8 && $y >= $n - 8);
    $trazo = '';
    for ($y = 0; $y < $n; $y++) {
        for ($x = 0; $x < $n; $x++) {
            if ($reservado($x, $y)) continue;
            if ((crc32("qr-$x-$y") % 100) < 47) $trazo .= "M$x {$y}h1v1h-1z";
        }
    }
    $marca = fn ($ox, $oy) => "M$ox $oy h7v7h-7z M".($ox + 1)." ".($oy + 1)."v5h5v-5z M".($ox + 2)." ".($oy + 2)."h3v3h-3z";
@endphp
<svg viewBox="-1 -1 {{ $n + 2 }} {{ $n + 2 }}" {{ $attributes }} aria-hidden="true" focusable="false">
    <rect x="-1" y="-1" width="{{ $n + 2 }}" height="{{ $n + 2 }}" fill="#F2EEE6"/>
    <path fill="#0E0D10" fill-rule="evenodd" d="{{ $trazo }}{{ $marca(0, 0) }}{{ $marca($n - 7, 0) }}{{ $marca(0, $n - 7) }}"/>
</svg>
