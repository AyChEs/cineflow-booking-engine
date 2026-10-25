@extends('layout')
@section('title', 'Marca')
@section('description', 'Sistema de marca de '.config('app.name').': símbolo, color, tipografía, motivo y movimiento.')
@section('content')
@php
    $colores = [
        ['Rojo telón', 'carmin-500', '#E0284A', 'Símbolo, botón principal, rótulos grandes', '4,2:1 sobre sala · blanco encima 4,6:1'],
        ['Sala', 'sala-950', '#0E0D10', 'Fondo', 'texto encima 16,7:1'],
        ['Superficie', 'sala-900', '#18161C', 'Tarjetas, mapa, paneles', 'texto encima 15,5:1'],
        ['Luz', 'texto', '#F2EEE6', 'Texto, nombre, haz', '16,7:1 sobre sala'],
        ['Penumbra', 'texto-2', '#A8A3AE', 'Texto secundario', '7,9:1 sobre sala'],
        ['Marquesina', 'marquesina', '#EBD67C', 'Solo lo tuyo y el foco', '12,3:1 sobre superficie'],
    ];
@endphp
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 md:py-16">
    <section aria-labelledby="marca-titulo" class="grid items-center gap-10 md:grid-cols-[1.2fr,1fr]">
        <div>
            <x-marca.logo tam="lg" />
            <h1 id="marca-titulo" class="display mt-8 text-[3.25rem] leading-[.9] text-texto md:text-[6rem]">La butaca es la entrada.</h1>
            <p class="mt-6 max-w-prose text-lg text-texto-2">En un cine no compras una película: compras un sitio en la oscuridad. La marca se construye con los tres objetos que lo cuentan, la butaca, la entrada perforada y el haz del proyector.</p>
            <p class="mt-4 text-sm text-texto-2">Guía completa en <code class="text-texto">docs/MARCA.md</code>.</p>
        </div>
        <figure class="relative mx-auto w-full max-w-sm">
            {{-- Construcción del símbolo sobre su retícula de 64 u --}}
            <svg viewBox="-4 -4 72 72" class="w-full" role="img" aria-label="Construcción del símbolo: respaldo con muescas y perforaciones de entrada, brazos y asiento, sobre una retícula de 64 unidades">
                <g stroke="#3A3641" stroke-width=".2">
                    @for ($i = 0; $i <= 64; $i += 4)
                        <line x1="{{ $i }}" y1="0" x2="{{ $i }}" y2="64" />
                        <line x1="0" y1="{{ $i }}" x2="64" y2="{{ $i }}" />
                    @endfor
                </g>
                <g fill="none" stroke="#A8A3AE" stroke-width=".3" stroke-dasharray="1 1">
                    <circle cx="15" cy="24" r="4.5" /><circle cx="49" cy="24" r="4.5" />
                    <circle cx="28" cy="19" r="13" /><circle cx="36" cy="19" r="13" />
                    <line x1="0" y1="24" x2="64" y2="24" />
                </g>
                <path fill="#E0284A" fill-rule="evenodd" d="M28 6H36A13 13 0 0 1 49 19V19.5A4.5 4.5 0 0 0 49 28.5V37A3 3 0 0 1 46 40H18A3 3 0 0 1 15 37V28.5A4.5 4.5 0 0 0 15 19.5V19A13 13 0 0 1 28 6ZM20.20 24a1.8 1.8 0 1 0 3.6 0a1.8 1.8 0 1 0 -3.6 0ZM26.87 24a1.8 1.8 0 1 0 3.6 0a1.8 1.8 0 1 0 -3.6 0ZM33.53 24a1.8 1.8 0 1 0 3.6 0a1.8 1.8 0 1 0 -3.6 0ZM40.20 24a1.8 1.8 0 1 0 3.6 0a1.8 1.8 0 1 0 -3.6 0ZM5 29A3 3 0 0 1 11 29V44H53V29A3 3 0 0 1 59 29V53A5 5 0 0 1 54 58H10A5 5 0 0 1 5 53Z" />
            </svg>
            <figcaption class="mt-3 text-center text-sm text-texto-2">Retícula de 64 u. Separación constante de 4 u.</figcaption>
        </figure>
    </section>

    <section aria-labelledby="marca-tamanos" class="mt-20 grid gap-6 md:grid-cols-2">
        <div class="rounded-card border border-sala-700 bg-sala-900 p-6">
            <h2 id="marca-tamanos" class="display text-3xl text-texto">De la pestaña al cartel</h2>
            <div class="mt-6 flex flex-wrap items-end gap-6 text-carmin-500">
                <svg viewBox="0 0 64 64" class="h-4 w-4" aria-hidden="true"><path fill="currentColor" d="M28 6H36A13 13 0 0 1 49 19V19.5A4.5 4.5 0 0 0 49 28.5V37A3 3 0 0 1 46 40H18A3 3 0 0 1 15 37V28.5A4.5 4.5 0 0 0 15 19.5V19A13 13 0 0 1 28 6ZM5 29A3 3 0 0 1 11 29V44H53V29A3 3 0 0 1 59 29V53A5 5 0 0 1 54 58H10A5 5 0 0 1 5 53Z"/></svg>
                <svg viewBox="0 0 64 64" class="h-8 w-8" aria-hidden="true"><path fill="currentColor" d="M28 6H36A13 13 0 0 1 49 19V19.5A4.5 4.5 0 0 0 49 28.5V37A3 3 0 0 1 46 40H18A3 3 0 0 1 15 37V28.5A4.5 4.5 0 0 0 15 19.5V19A13 13 0 0 1 28 6ZM5 29A3 3 0 0 1 11 29V44H53V29A3 3 0 0 1 59 29V53A5 5 0 0 1 54 58H10A5 5 0 0 1 5 53Z"/></svg>
                <x-marca.simbolo class="h-16 w-16" />
                <x-marca.simbolo class="h-28 w-28" />
            </div>
            <p class="mt-4 text-sm text-texto-2">Por debajo de 32 px, el símbolo reducido: sin perforaciones.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex items-center justify-center rounded-card border border-sala-700 bg-sala-950 p-6"><x-marca.logo /></div>
            <div class="flex items-center justify-center rounded-card bg-[#F2EEE6] p-6">
                <img src="{{ asset('img/marca/logo-horizontal-sobre-claro.svg') }}" alt="Logotipo sobre fondo claro" width="200" height="44" class="h-9 w-auto">
            </div>
            <div class="flex items-center justify-center rounded-card bg-carmin-500 p-6 text-white">
                <span class="marca-logo"><x-marca.simbolo class="marca-logo-simbolo !text-white" /><span class="marca-logo-nombre text-[1.75rem]">{{ config('app.name') }}</span></span>
            </div>
            <div class="flex items-center justify-center rounded-card border border-sala-700 bg-sala-900 p-6">
                <img src="{{ asset('img/marca/favicon.svg') }}" alt="Favicon" width="56" height="56" class="h-14 w-14">
            </div>
        </div>
    </section>

    <section aria-labelledby="marca-color" class="mt-20">
        <h2 id="marca-color" class="display text-4xl text-texto">Color</h2>
        <p class="mt-2 max-w-prose text-texto-2">70 % sala, 20 % luz, 10 % rojo telón. La marquesina solo aparece cuando algo es tuyo.</p>
        <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($colores as [$nombre, $token, $hex, $uso, $contraste])
                <li class="overflow-hidden rounded-card border border-sala-700 bg-sala-900">
                    <div class="h-24" style="background: {{ $hex }}"></div>
                    <div class="p-4">
                        <p class="font-semibold text-texto">{{ $nombre }} <span class="cifras font-normal text-texto-2">{{ $hex }}</span></p>
                        <p class="text-sm text-texto-2"><code>{{ $token }}</code> · {{ $uso }}</p>
                        <p class="mt-1 text-sm text-texto-2">{{ $contraste }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    <section aria-labelledby="marca-tipo" class="mt-20 grid gap-6 md:grid-cols-[1.4fr,1fr]">
        <div class="rounded-card border border-sala-700 bg-sala-900 p-6">
            <h2 id="marca-tipo" class="text-sm font-semibold text-texto-2">Big Shoulders Display · títulos</h2>
            <p class="display mt-4 text-[4rem] leading-[.9] text-texto md:text-[7rem]">Sala 3 · 22:15</p>
            <p class="display mt-2 text-3xl font-bold text-carmin-500">Fila F, butaca 7</p>
            <p class="mt-6 font-contorno text-[3.5rem] uppercase leading-none text-transparent md:text-[5rem]" style="-webkit-text-stroke: 1.5px #E0284A" aria-hidden="true">Estreno</p>
        </div>
        <div class="rounded-card border border-sala-700 bg-sala-900 p-6">
            <p class="text-sm font-semibold text-texto-2">Archivo · interfaz</p>
            <p class="mt-4 text-2xl font-semibold text-texto">Tu butaca queda guardada 8 minutos.</p>
            <p class="mt-3 text-texto-2">Horas, precios, filas y butacas con cifras tabulares: <span class="cifras text-texto">18:30 · 9,50 € · F7</span>.</p>
        </div>
    </section>

    <section aria-labelledby="marca-motivo" class="mt-20">
        <h2 id="marca-motivo" class="display text-4xl text-texto">La perforación</h2>
        <p class="mt-2 max-w-prose text-texto-2">Una idea en tres escalas: las muescas de la entrada, las perforaciones del celuloide y las filas de butacas.</p>
        <div class="mt-8 grid gap-6 md:grid-cols-3">
            <div class="entrada-muestra rounded-card p-6" aria-hidden="true">
                <span class="display text-3xl text-texto">Entrada</span>
                <span class="cifras mt-1 block text-sm text-texto-2">Sala 3 · F7</span>
            </div>
            <div class="celuloide rounded-card" aria-hidden="true"><div class="celuloide-cuadros"><span></span><span></span><span></span></div></div>
            <div class="flex flex-col items-center justify-center gap-1.5 rounded-card border border-sala-700 bg-sala-900 p-6" aria-hidden="true">
                @for ($f = 0; $f < 4; $f++)
                    <div class="flex gap-1.5">
                        @for ($c = 0; $c < 8; $c++)
                            <span class="seat seat--muestra {{ $f === 2 && $c === 4 ? 'seat--tuya' : ($f === 1 && $c > 4 ? 'seat--vendida' : 'seat--libre') }}"></span>
                        @endfor
                    </div>
                @endfor
            </div>
        </div>
    </section>

    <section aria-labelledby="marca-archivos" class="mt-20">
        <h2 id="marca-archivos" class="display text-4xl text-texto">Archivos</h2>
        <ul class="mt-6 flex flex-wrap gap-3">
            @foreach (['simbolo.svg' => 'Símbolo', 'logo-horizontal.svg' => 'Logotipo horizontal', 'logo-horizontal-sobre-claro.svg' => 'Sobre claro', 'logo-mono-claro.svg' => 'Monocromo claro', 'logo-mono-oscuro.svg' => 'Monocromo oscuro', 'favicon.svg' => 'Favicon', 'og.png' => 'Imagen para redes'] as $archivo => $texto)
                <li><a class="btn btn-secondary btn-sm" href="{{ asset('img/marca/'.$archivo) }}" download>{{ $texto }}</a></li>
            @endforeach
        </ul>
    </section>
</div>
@endsection
