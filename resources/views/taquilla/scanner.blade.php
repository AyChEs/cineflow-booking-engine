@extends('layout')

@section('title', 'Box Office · Validate Tickets')

@section('content')
<div style="max-width:760px;margin:0 auto;padding:2.5rem 1.5rem 4rem;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;">
        <div>
            <p style="color:var(--color-text-secondary);font-size:0.72rem;letter-spacing:2px;text-transform:uppercase;margin:0 0 0.25rem;">BOX OFFICE</p>
            <h1 style="font-size:1.6rem;font-weight:900;margin:0;letter-spacing:-0.5px;">Validate Tickets</h1>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left" style="margin-right:6px;"></i> Dashboard
        </a>
    </div>

    {{-- Camera scanner --}}
    <div style="background:var(--color-bg-surface);border:1px solid var(--border-subtle);border-radius:16px;padding:1.5rem;box-shadow:0 8px 24px var(--shadow-soft);margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <span style="font-weight:800;font-size:0.8rem;letter-spacing:1.5px;text-transform:uppercase;">
                <i class="fas fa-camera" style="color:var(--color-accent-bright);margin-right:8px;"></i>Camera Scanner
            </span>
            <button id="btnToggleCam" class="btn btn-primary btn-sm" type="button">Start Camera</button>
        </div>

        <div id="reader" style="width:100%;max-width:420px;margin:0 auto;border-radius:12px;overflow:hidden;"></div>
        <p id="camHint" style="text-align:center;color:var(--color-text-secondary);font-size:0.8rem;margin:0.75rem 0 0;">
            Point the camera at the ticket's QR code.
        </p>
    </div>

    {{-- Manual validation --}}
    <div style="background:var(--color-bg-surface);border:1px solid var(--border-subtle);border-radius:16px;padding:1.5rem;box-shadow:0 8px 24px var(--shadow-soft);margin-bottom:1.5rem;">
        <span style="font-weight:800;font-size:0.8rem;letter-spacing:1.5px;text-transform:uppercase;display:block;margin-bottom:0.85rem;">
            <i class="fas fa-keyboard" style="color:var(--color-accent-bright);margin-right:8px;"></i>Manual Entry
        </span>
        <div style="display:flex;gap:0.6rem;flex-wrap:wrap;">
            <input id="manualInput" type="text" placeholder="Paste the ticket code (ID.TOKEN.SIGNATURE)"
                   class="form-input" style="flex:1;min-width:220px;font-family:monospace;font-size:0.85rem;">
            <button id="btnManual" class="btn btn-primary" type="button">Validate</button>
        </div>
    </div>

    {{-- Resultado --}}
    <div id="result" style="display:none;border-radius:16px;padding:1.5rem;text-align:center;"></div>

</div>

{{-- html5-qrcode: in-browser camera scanning (no backend) --}}
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    const validarBase = "{{ url('/entrada/validar') }}";
    const resultEl  = document.getElementById('result');
    const manualIn  = document.getElementById('manualInput');
    const btnManual = document.getElementById('btnManual');
    const btnCam    = document.getElementById('btnToggleCam');
    const camHint   = document.getElementById('camHint');

    let lastPayload = null;
    let lastAt = 0;
    let busy = false;

    function renderResult(state, data) {
        resultEl.style.display = 'block';
        if (state === 'valid') {
            const r = data.reserva;
            resultEl.style.background = '#e9f9ef';
            resultEl.style.border = '1px solid #34d17f';
            resultEl.innerHTML = `
                <div style="font-size:2.4rem;color:#22a35a;margin-bottom:0.4rem;"><i class="fas fa-check-circle"></i></div>
                <h2 style="margin:0 0 0.75rem;color:#12603a;font-weight:900;">VALID TICKET</h2>
                <div style="text-align:left;max-width:340px;margin:0 auto;font-size:0.9rem;color:#1d1418;display:flex;flex-direction:column;gap:0.35rem;">
                    <div><strong>Booking:</strong> #${r.id}</div>
                    <div><strong>Movie:</strong> ${r.pelicula ?? '—'}</div>
                    <div><strong>Showtime:</strong> ${r.fecha ?? '—'}</div>
                    <div><strong>Seats:</strong> ${r.butaques || '—'}</div>
                    <div><strong>Total:</strong> ${Number(r.total ?? 0).toFixed(2)} €</div>
                </div>`;
        } else {
            resultEl.style.background = '#fdeaea';
            resultEl.style.border = '1px solid #e2757a';
            resultEl.innerHTML = `
                <div style="font-size:2.4rem;color:#c0392b;margin-bottom:0.4rem;"><i class="fas fa-times-circle"></i></div>
                <h2 style="margin:0 0 0.35rem;color:#8f1f1f;font-weight:900;">INVALID TICKET</h2>
                <p style="margin:0;color:#8f1f1f;font-size:0.88rem;">Incorrect, expired, or already used code.</p>`;
        }
        resultEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function validar(payload) {
        payload = (payload || '').trim();
        if (!payload || busy) return;
        // Avoid re-sending the same QR within 3s (the camera fires repeatedly)
        const now = Date.now();
        if (payload === lastPayload && (now - lastAt) < 3000) return;
        lastPayload = payload; lastAt = now; busy = true;

        try {
            const res = await fetch(`${validarBase}/${encodeURIComponent(payload)}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = res.ok ? await res.json() : { valid: false };
            renderResult(data.valid ? 'valid' : 'invalid', data);
        } catch (e) {
            renderResult('invalid', {});
        } finally {
            busy = false;
        }
    }

    btnManual.addEventListener('click', () => validar(manualIn.value));
    manualIn.addEventListener('keydown', (e) => { if (e.key === 'Enter') validar(manualIn.value); });

    // Camera (html5-qrcode)
    let html5Qr = null;
    let scanning = false;

    async function startCam() {
        if (!window.Html5Qrcode) { camHint.textContent = 'Could not load the scanner.'; return; }
        html5Qr = new Html5Qrcode('reader');
        try {
            await html5Qr.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 240, height: 240 } },
                (decoded) => validar(decoded),
                () => {}
            );
            scanning = true;
            btnCam.textContent = 'Stop Camera';
            camHint.textContent = 'Scanning… bring the QR code into the frame.';
        } catch (err) {
            camHint.textContent = 'Could not access the camera. Use manual entry instead.';
        }
    }

    async function stopCam() {
        if (html5Qr && scanning) {
            try { await html5Qr.stop(); await html5Qr.clear(); } catch (e) {}
        }
        scanning = false;
        btnCam.textContent = 'Start Camera';
        camHint.textContent = "Point the camera at the ticket's QR code.";
    }

    btnCam.addEventListener('click', () => { scanning ? stopCam() : startCam(); });
})();
</script>
@endsection
