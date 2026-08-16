{{-- Purchase summary sidebar, shared between the 3 steps --}}
<aside class="compra-sidebar bg-white rounded-xl p-6 border border-gray-200" style="position:sticky; top:5.5rem;">

    <h3 class="text-red-700 text-base font-bold mb-4">Order Summary</h3>

    <div class="flex gap-4 mb-4">

        {{-- Movie thumbnail (initials as placeholder) --}}
        <div class="w-14 h-20 bg-gray-200 rounded flex-shrink-0 flex items-center justify-center overflow-hidden">
            <span class="text-2xl font-black text-gray-400">
                {{ strtoupper(substr($sesion->pelicula->titulo, 0, 2)) }}
            </span>
        </div>

        {{-- Session information --}}
        <div class="text-xs text-gray-700 leading-relaxed">
            <div class="font-bold text-sm mb-0.5">{{ $sesion->pelicula->titulo }}</div>
            <div><span class="text-gray-400">Cinema:</span> {{ $sesion->sala->cine->nombre }}</div>
            <div><span class="text-gray-400">Date:</span>
                {{ $sesion->fecha_hora->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
            </div>
            <div><span class="text-gray-400">Showtime:</span> {{ $sesion->fecha_hora->format('H:i') }}</div>

            @if($step >= 2 && $compra)
                @php
                    $labels = [];
                    foreach ($compra['entrades'] as $k => $q) {
                        if ($q > 0)
                            $labels[] = "$q " . ucfirst($k);
                    }
                @endphp
                <div><span class="text-gray-400">Tickets:</span> {{ implode(', ', $labels) }}</div>
            @endif

            @if($step >= 3 && $compra && !empty($compra['butaques']))
                <div><span class="text-gray-400">Seats:</span> {{ implode(', ', $compra['butaques']) }}</div>
            @endif
        </div>
    </div>

    {{-- Total --}}
    <div class="border-t border-gray-200 pt-3 flex justify-between items-center">
        <span class="font-bold text-sm">Total (Taxes Included):</span>
        <span id="sidebarTotal" class="font-bold text-base">
            {{ $compra ? number_format($compra['total'], 2, ',', '') . ' €' : '0 €' }}
        </span>
    </div>
    <p class="text-gray-400 text-[0.72rem] mt-1">Booking fees, VAT/IGIC and surcharges included.</p>

</aside>