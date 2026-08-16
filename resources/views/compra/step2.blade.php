@extends('layout')
@section('title', 'Checkout – Step 2: Seats')
@section('content')

@include('compra._progress', ['step' => 2])

<style>
/* Custom CSS for seat states — independent from Tailwind for maximum compatibility */
.seat-btn {
    width: 22px;
    height: 22px;
    border: none;
    border-radius: 4px;
    font-size: 0.6rem;
    color: rgba(255,255,255,0.85);
    cursor: pointer;
    transition: transform 0.1s ease-out, background-color 0.15s ease;
    font-weight: 500;
}

.seat-btn.free {
    background-color: #9ca3af;
    cursor: pointer;
}

.seat-btn.free:hover {
    transform: scale(1.05);
    background-color: #b5bdc4;
}

.seat-btn.mine {
    background-color: #16a34a;
    cursor: pointer;
}

.seat-btn.mine:hover {
    transform: scale(1.05);
    background-color: #22c55e;
}

.seat-btn.taken {
    background-color: #dc2626;
    cursor: not-allowed;
    opacity: 0.7;
}

.seat-btn.locked {
    background-color: #d97706;
    cursor: not-allowed;
    opacity: 0.6;
}

.seat-btn:disabled {
    cursor: not-allowed;
}

/* Responsive layout */
.step2-container {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 2rem;
    align-items: start;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
    margin-top: 2.5rem;
    margin-bottom: 2.5rem;
    padding-left: 1.5rem;
    padding-right: 1.5rem;
}

@media (max-width: 1024px) {
    .step2-container {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    .compra-sidebar { position: static !important; }
}

@media (max-width: 600px) {
    .step2-container {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
        margin-top: 1.25rem;
        margin-bottom: 1.25rem;
    }
    .step2-main { padding: 1.25rem 1rem; }
    /* Larger seats for touch */
    .seat-btn {
        width: 26px;
        height: 26px;
        font-size: 0.55rem;
    }
    .seats-table { border-spacing: 3px 3px; }
    /* Full-width button on mobile */
    .btn-next { width: 100%; }
}

.step2-main {
    background-color: white;
    border-radius: 12px;
    padding: 2rem;
    overflow-x: auto;
}

.step2-title {
    color: #7c2d12;
    font-size: 1.25rem;
    font-weight: bold;
    text-align: center;
    margin-bottom: 0.5rem;
}

.step2-subtitle {
    text-align: center;
    color: #9ca3af;
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
}

.legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1.25rem;
    margin-bottom: 1.5rem;
    font-size: 0.75rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
}

.legend-label {
    color: #9ca3af;
}

.screen {
    text-align: center;
    margin-bottom: 1.5rem;
}

.screen-bar {
    display: inline-block;
    width: 60%;
    height: 8px;
    border-radius: 4px;
    background: linear-gradient(to right, transparent, #9ca3af, transparent);
}

.screen-text {
    color: #9ca3af;
    font-size: 0.7rem;
    letter-spacing: 3px;
    margin-top: 0.25rem;
}

.seats-table {
    border-spacing: 4px 3px;
    margin-left: auto;
    margin-right: auto;
}

.seats-table td {
    padding: 0;
}

.row-label {
    color: #9ca3af;
    font-size: 0.7rem;
    width: 18px;
    text-align: center;
}

.step2-form {
    margin-top: 1.5rem;
    text-align: center;
}

.selection-info {
    font-size: 0.875rem;
    color: #9ca3af;
    margin-bottom: 0.75rem;
}

.selection-list {
    color: #16a34a;
    font-weight: 600;
}

.btn-next {
    background-color: #d1d5db;
    color: white;
    border-radius: 9999px;
    padding: 0.75rem 3.5rem;
    font-size: 1rem;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: not-allowed;
    transition: all 0.2s;
    border: none;
}

.btn-next:disabled {
    cursor: not-allowed;
}

.btn-next.active {
    background-color: #1e3a5f;
    cursor: pointer;
}

.btn-next.active:hover {
    background-color: #152a45;
}
</style>

<div class="step2-container">
    {{-- Main seat map panel --}}
    <div class="step2-main">
        <h2 class="step2-title">Select your seats</h2>
        <p class="step2-subtitle">
            You must select <strong id="numRequired">{{ $compra['num_entrades'] }}</strong> seat(s).
        </p>

        @if(session('error'))
            <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem;">
                {{ session('error') }}
            </div>
        @endif

        {{-- Seat status legend --}}
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background-color: #9ca3af;"></div>
                <span class="legend-label">Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: #16a34a;"></div>
                <span class="legend-label">Selected</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: #dc2626;"></div>
                <span class="legend-label">Sold</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: #d97706;"></div>
                <span class="legend-label">Held by others</span>
            </div>
        </div>

        {{-- Screen indicator --}}
        <div class="screen">
            <div class="screen-bar"></div>
            <div class="screen-text">SCREEN</div>
        </div>

        {{-- Seat map dynamically generated from the room layout --}}
        @php
            $rows       = $layout['rows'];
            $spr        = $layout['seatsPerRow'];
            $lastSeats  = $layout['lastRowSeats'];
            $takenSet   = array_flip($takenSeats);
            $lockedSet  = array_flip($lockedSeats);
            $mySet      = array_flip($myLocks);
            $rowLabels  = range('A', 'Z');
        @endphp

        <div style="overflow-x: auto; padding-bottom: 1rem;">
        <table class="seats-table">
            @for($r = 0; $r < $rows; $r++)
            @php
                $seatsInThisRow = ($r === $rows - 1) ? $lastSeats : $spr;
                $rowLabel = $rowLabels[$r] ?? ($r + 1);
            @endphp
            <tr>
                <td class="row-label">{{ $rowLabel }}</td>

                @for($s = 1; $s <= $seatsInThisRow; $s++)
                @php
                    $id = $rowLabel . $s;
                    $isTaken  = isset($takenSet[$id]);
                    $isLocked = isset($lockedSet[$id]);
                    $isMine   = isset($mySet[$id]);

                    if ($isTaken) {
                        $seatState = 'taken';
                        $title = 'Sold';
                    } elseif ($isLocked) {
                        $seatState = 'locked';
                        $title = 'Held';
                    } elseif ($isMine) {
                        $seatState = 'mine';
                        $title = 'Selected';
                    } else {
                        $seatState = 'free';
                        $title = 'Available';
                    }

                    $clickable = !$isTaken && !$isLocked;
                @endphp
                <td>
                    <button type="button"
                        class="seat-btn {{ $seatState }}"
                        data-seat="{{ $id }}"
                        data-state="{{ $seatState }}"
                        onclick="{{ $clickable ? 'toggleSeat(this)' : '' }}"
                        title="{{ $title }} {{ $id }}"
                        {{ !$clickable ? 'disabled' : '' }}>{{ $s }}</button>
                </td>
                @endfor

                <td class="row-label">{{ $rowLabel }}</td>
            </tr>
            @endfor
        </table>
        </div>

        {{-- Submit form with the list of selected seats --}}
        <form method="POST" action="{{ route('compra.step2.store') }}" id="step2form" class="step2-form">
            @csrf
            <input type="hidden" name="butaques" id="butaquesInput" value="">

            <p class="selection-info">
                Selected: <strong id="selCount">0</strong> / {{ $compra['num_entrades'] }}
                &nbsp;·&nbsp;
                <span id="selList" class="selection-list"></span>
            </p>

            <button type="submit" id="btnSeguent" disabled class="btn-next">
                NEXT
            </button>
        </form>
    </div>

    {{-- Summary sidebar --}}
    @include('compra._sidebar', ['sesion' => $sesion, 'step' => 2, 'compra' => $compra])
</div>

<script>
// Number of seats the user must select
const REQUIRED  = {{ $compra['num_entrades'] }};
const SESION_ID = {{ $sesion->id }};

// Seats already locked by this user
let selected = new Set(@json($myLocks));

// Updates the UI with the current selection state
function render() {
    document.querySelectorAll('[data-seat]').forEach(btn => {
        if (btn.disabled) return;

        if (selected.has(btn.dataset.seat)) {
            btn.className = 'seat-btn mine';
        } else {
            btn.className = 'seat-btn free';
        }
    });

    let list = Array.from(selected).sort();
    document.getElementById('selCount').textContent     = list.length;
    document.getElementById('selList').textContent      = list.join(', ');
    document.getElementById('butaquesInput').value      = list.join(',');

    let btn = document.getElementById('btnSeguent');
    if (list.length === REQUIRED) {
        btn.classList.add('active');
        btn.disabled = false;
    } else {
        btn.classList.remove('active');
        btn.disabled = true;
    }
}

async function toggleSeat(btn) {
    let seat = btn.dataset.seat;

    if (selected.has(seat)) {
        selected.delete(seat);
        await fetch('{{ route("seat.unlock") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ sesion_id: SESION_ID, butaca: seat })
        });
        render();
    } else {
        if (selected.size >= REQUIRED) {
            let oldest = Array.from(selected)[0];
            selected.delete(oldest);
            await fetch('{{ route("seat.unlock") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ sesion_id: SESION_ID, butaca: oldest })
            });
        }
        
        let res = await fetch('{{ route("seat.lock") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ sesion_id: SESION_ID, butaca: seat })
        });
        let data = await res.json();

        if (data.ok) {
            selected.add(seat);
        } else {
            btn.className = data.reason === 'taken' ? 'seat-btn taken' : 'seat-btn locked';
            btn.disabled = true;
            alert('Seat ' + seat + ' was just taken by another user.');
        }
        render();
    }
}

async function pollSeats() {
    try {
        let res = await fetch('/api/seats/' + SESION_ID);
        let data = await res.json();

        document.querySelectorAll('[data-seat]').forEach(btn => {
            let seat = btn.dataset.seat;

            if (data.taken.includes(seat) && !selected.has(seat)) {
                btn.className = 'seat-btn taken';
                btn.disabled = true;
            } else if (data.locked.includes(seat) && !selected.has(seat)) {
                btn.className = 'seat-btn locked';
                btn.disabled = true;
            }
        });
    } catch(e) {}
}

render();
setInterval(pollSeats, 10000);
</script>
@endsection
