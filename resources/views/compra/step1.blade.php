@extends('layout')
@section('title', 'Checkout – Step 1: Tickets')
@section('content')

<style>
/* Layout step 1 */
.step1-container {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
    max-width: 900px;
    margin: 2.5rem auto;
    padding: 0 1.5rem;
}
.step1-main {
    flex: 1;
    min-width: 0;
    background: #fff;
    border-radius: 14px;
    padding: 2.5rem 2rem;
}
.step1-sidebar {
    width: 320px;
    flex-shrink: 0;
    position: sticky;
    top: 5.5rem;
    align-self: flex-start;
}
/* Row for each ticket type */
.entry-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f3f4f6;
    gap: 1rem;
}
.entry-row-info { flex: 1; min-width: 0; }
.entry-row-price {
    font-weight: 600;
    color: #374151;
    font-size: 0.88rem;
    min-width: 52px;
    text-align: right;
    white-space: nowrap;
}
.entry-row-qty {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}
.qty-btn {
    width: 2rem; height: 2rem;
    border: 1px solid #d1d5db;
    border-radius: 50%;
    background: #fff;
    font-size: 1.1rem;
    display: flex; align-items: center; justify-content: center;
    color: #6b7280;
    cursor: pointer;
    transition: border-color 0.15s;
    flex-shrink: 0;
}
.qty-btn:hover { border-color: #9ca3af; }
.qty-input {
    width: 2.5rem;
    text-align: center;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 0.25rem 0;
    font-size: 0.88rem;
    font-weight: 700;
    background: #fff;
    color: #111827;
}
/* Responsive: mobile ≤ 700px — sidebar goes below */
@media (max-width: 700px) {
    .step1-container {
        flex-direction: column;
        margin: 1.25rem auto;
        padding: 0 1rem;
        gap: 1.25rem;
    }
    .step1-main { padding: 1.5rem 1.25rem; }
    .step1-sidebar {
        width: 100%;
        position: static;
    }
    .entry-row { flex-wrap: wrap; gap: 0.6rem; }
}
</style>

{{-- Progress bar --}}
@include('compra._progress', ['step' => 1])

{{-- Main layout: left panel + sticky sidebar on the right --}}
<div class="step1-container">

    {{-- Main ticket selection panel --}}
    <div class="step1-main">

        <h2 style="color:#b91c1c; font-size:1.1rem; font-weight:900; text-align:center; margin-bottom:0.4rem; letter-spacing:0.05em;">
            Select your tickets
        </h2>
        <p style="text-align:center; color:#9ca3af; font-size:0.85rem; margin-bottom:2rem;">
            You can buy a maximum of <strong>10 tickets</strong> per transaction.
        </p>

        @if(session('error'))
            <div style="background:#fef2f2; border:1px solid #fca5a5; color:#dc2626; padding:0.75rem 1rem; border-radius:8px; font-size:0.85rem; margin-bottom:1.25rem;">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('compra.step1.store') }}" id="step1form">
            @csrf
            <input type="hidden" name="sesion_id" value="{{ $sesion->id }}">

            {{-- Ticket types table --}}
            <div style="border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:2rem;">

                <div style="background:#f3f4f6; padding:0.6rem 1.25rem; font-size:0.7rem; font-weight:900;
                            letter-spacing:0.18em; text-transform:uppercase; color:#6b7280;
                            border-bottom:1px solid #e5e7eb;">
                    STANDARD
                </div>

                @foreach($tipus as $key => $info)
                <div class="entry-row">
                    <div class="entry-row-info">
                        <div style="font-weight:700; font-size:0.88rem; color:#111827;">{{ strtoupper($info['label']) }}</div>
                        @if($info['desc'])
                            <div style="color:#9ca3af; font-size:0.75rem; margin-top:2px;">{{ $info['desc'] }}</div>
                        @endif
                    </div>

                    <div class="entry-row-price">
                        {{ number_format($sesion->preu_base * $info['factor'], 2, ',', '') }} €
                    </div>

                    <div class="entry-row-qty">
                        <button type="button" class="qty-btn" onclick="changeQty('{{ $key }}', -1)">&minus;</button>

                        <input type="number" name="entrades[{{ $key }}]" id="qty_{{ $key }}"
                               value="{{ $entrades[$key] ?? 0 }}" min="0" max="10" readonly
                               class="qty-input">

                        <button type="button" class="qty-btn" onclick="changeQty('{{ $key }}', +1)">+</button>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Continue button --}}
            <div style="text-align:center;">
                <button type="submit" id="btnSeguent"
                    style="background:#d1d5db; color:#fff; border:none; border-radius:999px;
                           padding:0.85rem 3.5rem; font-size:0.9rem; font-weight:900;
                           letter-spacing:0.15em; text-transform:uppercase;
                           cursor:not-allowed; opacity:0.55;
                           transition:background 0.2s, opacity 0.2s;"
                    disabled>
                    <i class="fas fa-arrow-right" style="margin-right:0.5rem;"></i>NEXT
                </button>
            </div>
        </form>
    </div>

    {{-- Summary sidebar — sticky on the right --}}
    <div class="step1-sidebar">
        @include('compra._sidebar', ['sesion' => $sesion, 'step' => 1, 'compra' => null])
    </div>
</div>

<script>
const preus = @json(collect($tipus)->map(fn($t) => $sesion->preu_base * $t['factor']));
const keys  = @json(array_keys($tipus));

function changeQty(key, delta) {
    const el       = document.getElementById('qty_' + key);
    const totalNow = keys.reduce((s, k) => s + parseInt(document.getElementById('qty_' + k).value || 0), 0);
    if (delta > 0 && totalNow >= 10) return;
    const val = Math.max(0, parseInt(el.value || 0) + delta);
    el.value = val;
    updateTotal();
}

function updateTotal() {
    let total = 0, num = 0;
    keys.forEach(k => {
        const q = parseInt(document.getElementById('qty_' + k).value || 0);
        total += q * (preus[k] || 0);
        num   += q;
    });

    const btn = document.getElementById('btnSeguent');
    if (num > 0) {
        btn.style.background = '#b91c1c';
        btn.style.cursor     = 'pointer';
        btn.style.opacity    = '1';
        btn.disabled         = false;
    } else {
        btn.style.background = '#d1d5db';
        btn.style.cursor     = 'not-allowed';
        btn.style.opacity    = '0.55';
        btn.disabled         = true;
    }

    const totalEl = document.getElementById('sidebarTotal');
    if (totalEl) totalEl.textContent = total.toFixed(2).replace('.', ',') + ' €';
}

updateTotal();
</script>
@endsection