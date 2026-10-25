@extends('layout')
@section('title', 'Elige tus butacas')
@section('content')

@include('compra._progress', ['step' => 2])

@php
    $rows = $layout['rows'];
    $spr = $layout['seatsPerRow'];
    $lastSeats = $layout['lastRowSeats'];
    $takenSet = array_flip($takenSeats);
    $lockedSet = array_flip($lockedSeats);
    $mySet = array_flip($myLocks);
    $rowLabels = range('A', 'Z');
    $requeridas = (int) $compra['num_entrades'];
    $textoEstado = ['libre' => 'libre', 'tuya' => 'seleccionada por ti', 'retenida' => 'retenida por otra persona', 'vendida' => 'vendida'];
    $primeraFoco = null;
    $expira = $expiresAt?->toIso8601String();
@endphp

<div class="mx-auto grid max-w-6xl items-start gap-6 px-4 py-pagina sm:px-6 lg:grid-cols-[1fr,20rem]">
    <div class="min-w-0 rounded-card border border-sala-700 bg-sala-900 p-4 sm:p-6">
        <h1 class="display text-3xl text-texto">Elige tus butacas</h1>
        <p class="mt-2 text-texto-2">Necesitas <strong class="cifras text-texto">{{ $requeridas }}</strong> {{ $requeridas === 1 ? 'butaca' : 'butacas' }}. {{ $sesion->sala->nombre }}, {{ $sesion->sala->cine->nombre }}.</p>

        @if (session('error'))
            <div class="alert alert-error mt-5" role="alert"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />{{ session('error') }}</div>
        @endif

        <div id="seat-aviso" class="alert alert-error mt-5" hidden></div>
        <div id="fin-reserva" class="alert alert-error mt-5 focus:outline-none" tabindex="-1" hidden>
            <x-icon name="clock" class="mt-0.5 h-4 w-4 shrink-0" /><span>El tiempo de reserva ha terminado. Vuelve a elegir tus butacas.</span>
        </div>

        {{-- Leyenda: las cuatro muestras con el mismo dibujo que en el mapa --}}
        <ul id="seat-legend" class="mt-6 flex flex-wrap gap-x-5 gap-y-3 text-sm text-texto-2" aria-label="Leyenda">
            <li class="flex items-center gap-2"><span class="seat seat--libre seat--muestra" aria-hidden="true"><span class="seat-num">7</span></span>Libre</li>
            <li class="flex items-center gap-2"><span class="seat seat--tuya seat--muestra" aria-hidden="true"><span class="seat-num">7</span></span>Tuya</li>
            <li class="flex items-center gap-2"><span class="seat seat--retenida seat--muestra" aria-hidden="true"><span class="seat-num">7</span></span>Retenida por otra persona</li>
            <li class="flex items-center gap-2"><span class="seat seat--vendida seat--muestra" aria-hidden="true"><span class="seat-num">7</span></span>Vendida</li>
        </ul>
        <p id="seat-help" class="mt-3 text-sm text-texto-2">Usa las flechas para moverte y Espacio para elegir.</p>

        <div class="mt-6 overflow-x-auto pb-2" tabindex="0" role="region" aria-label="Mapa de butacas, se puede desplazar en horizontal">
            <div class="mx-auto w-max px-1">
                <div class="screen" aria-hidden="true"><span>Pantalla</span></div>

                <div id="seat-map" role="grid" aria-label="Butacas de {{ mb_strtolower($sesion->sala->nombre) }}"
                     aria-describedby="seat-legend seat-help"
                     data-required="{{ $requeridas }}" data-sesion="{{ $sesion->id }}"
                     data-lock-url="{{ route('seat.lock') }}" data-unlock-url="{{ route('seat.unlock') }}"
                     data-status-url="{{ route('seat.status', $sesion->id) }}"
                     data-expira="{{ $expira }}"
                     class="flex flex-col gap-2">
                    @for ($r = 0; $r < $rows; $r++)
                        @php
                            $enFila = ($r === $rows - 1) ? $lastSeats : $spr;
                            $fila = $rowLabels[$r] ?? (string) ($r + 1);
                        @endphp
                        <div role="row" class="flex items-center gap-1.5">
                            <span role="rowheader" class="cifras w-6 shrink-0 text-center text-sm font-semibold text-texto-2"><span class="sr-only">Fila </span>{{ $fila }}</span>
                            @for ($s = 1; $s <= $enFila; $s++)
                                @php
                                    $id = $fila.$s;
                                    $estado = isset($takenSet[$id]) ? 'vendida' : (isset($lockedSet[$id]) ? 'retenida' : (isset($mySet[$id]) ? 'tuya' : 'libre'));
                                    if ($primeraFoco === null && $estado === 'tuya') { $primeraFoco = $id; }
                                @endphp
                                <span role="gridcell">
                                    <button type="button" class="seat seat--{{ $estado }}" data-seat="{{ $id }}" data-fila="{{ $fila }}" data-num="{{ $s }}"
                                            data-estado="{{ $estado }}"
                                            aria-pressed="{{ $estado === 'tuya' ? 'true' : 'false' }}"
                                            @if (in_array($estado, ['vendida', 'retenida'])) aria-disabled="true" @endif
                                            aria-label="Fila {{ $fila }}, butaca {{ $s }}, {{ $textoEstado[$estado] }}"
                                            tabindex="-1"><span class="seat-num">{{ $s }}</span></button>
                                </span>
                            @endfor
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <p id="seat-status" class="sr-only" aria-live="polite"></p>

        <form method="POST" action="{{ route('compra.step2.store') }}" id="step2form" class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-sala-700 pt-6">
            @csrf
            <input type="hidden" name="butaques" id="butaquesInput" value="{{ implode(',', $myLocks) }}">
            <p class="text-texto-2"><span class="cifras font-semibold text-texto" id="selCount">{{ count($myLocks) }}</span> de <span class="cifras">{{ $requeridas }}</span> elegidas</p>
            <div class="flex flex-col items-end gap-1.5">
                <button type="submit" id="btnSeguent" class="btn btn-primary btn-lg btn-brillo" aria-describedby="ayuda-continuar"
                        aria-disabled="{{ count($myLocks) === $requeridas ? 'false' : 'true' }}">
                    Continuar<x-icon name="arrow-right" class="h-5 w-5" />
                </button>
                <p id="ayuda-continuar" class="text-sm text-texto-2" @if (count($myLocks) === $requeridas) hidden @endif>
                    Elige {{ $requeridas }} {{ $requeridas === 1 ? 'butaca' : 'butacas' }} para continuar
                </p>
            </div>
        </form>
    </div>

    @include('compra._sidebar', ['sesion' => $sesion, 'step' => 2, 'compra' => $compra, 'expiresAt' => $expiresAt])
</div>
@endsection
