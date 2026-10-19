{{-- Resumen de la compra, compartido por los tres pasos. --}}
@php
    $expiresAt = $expiresAt ?? null;
    $tipos = \App\Http\Controllers\CompraController::TIPUS;
    $entradas = collect($compra['entrades'] ?? [])->filter()->map(fn ($n, $k) => $n.' × '.($tipos[$k]['label'] ?? $k));
    $butacas = collect($compra['butaques'] ?? [])->sortBy(fn ($b) => sprintf('%s%03d', preg_replace('/\d+/', '', $b), (int) preg_replace('/\D+/', '', $b)))->values();
@endphp
<aside class="h-fit rounded-card border border-sala-700 bg-sala-900 p-4 sm:p-6 lg:sticky lg:top-24" aria-labelledby="resumen-titulo">
    <h2 id="resumen-titulo" class="mb-4 text-lg font-semibold text-texto">Tu compra</h2>

    <div class="flex gap-4">
        <img src="{{ $sesion->pelicula->poster_url }}" alt="" width="300" height="450" decoding="async"
             class="aspect-[2/3] w-16 shrink-0 rounded-control border border-sala-700 object-cover">
        <dl class="min-w-0 text-sm">
            <dt class="sr-only">Película</dt>
            <dd class="font-semibold text-texto">{{ $sesion->pelicula->titulo }}</dd>
            <dt class="sr-only">Cine y sala</dt>
            <dd class="text-texto-2">{{ $sesion->sala->cine->nombre }} · {{ $sesion->sala->nombre }}</dd>
            <dt class="sr-only">Día y hora</dt>
            <dd class="cifras text-texto-2">{{ ucfirst($sesion->fecha_hora->locale('es')->isoFormat('dddd D [de] MMMM')) }}, {{ $sesion->fecha_hora->format('G:i') }}</dd>
        </dl>
    </div>

    <dl class="mt-5 grid grid-cols-[auto,1fr] gap-x-4 gap-y-2 border-t border-sala-700 pt-4 text-sm">
        @if ($step >= 2 && $entradas->isNotEmpty())
            <dt class="text-texto-2">Entradas</dt>
            <dd class="cifras text-right text-texto">{{ $entradas->join(', ') }}</dd>
        @endif
        @if ($step >= 2)
            <dt class="text-texto-2">Butacas</dt>
            <dd class="cifras text-right font-semibold text-marquesina" id="resumen-butacas">{{ $butacas->isNotEmpty() ? $butacas->join(', ') : 'Sin elegir' }}</dd>
        @endif
        <dt class="font-semibold text-texto">Total</dt>
        <dd class="cifras text-right text-lg font-semibold text-texto" id="sidebarTotal" aria-live="polite">{{ $compra ? number_format($compra['total'], 2, ',', '.').' €' : '0,00 €' }}</dd>
    </dl>
    <p class="mt-1 text-xs text-texto-2">IVA incluido.</p>

    @if ($step >= 2)
        {{-- Cuenta atrás del bloqueo: visible cada segundo, anunciada solo a los 5, 2 y 1 minutos y a los 30 s. --}}
        <div id="cuenta-atras" class="mt-5 rounded-control border border-sala-700 bg-sala-950 p-3 text-sm {{ $expiresAt ? '' : 'hidden' }}"
             data-expires="{{ $expiresAt?->toIso8601String() }}">
            <p class="flex items-center gap-2 text-texto">
                <x-icon name="clock" class="h-4 w-4 shrink-0 text-marquesina" />
                <span role="timer" id="cuenta-atras-timer" aria-label="Tus butacas están reservadas">
                    <span aria-hidden="true">Tus butacas están reservadas durante <strong class="cifras" id="cuenta-atras-valor">8:00</strong></span>
                </span>
            </p>
            <p class="sr-only" aria-live="polite" id="cuenta-atras-aviso"></p>
        </div>
        <p id="cuenta-atras-nota" class="mt-5 text-sm text-texto-2 {{ $expiresAt ? 'hidden' : '' }}">Cuando elijas butacas, te las guardamos 8 minutos.</p>
    @endif
</aside>
