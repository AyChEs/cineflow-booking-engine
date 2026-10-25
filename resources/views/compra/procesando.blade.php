@extends('layout')
@section('title', 'Confirmando tu pago')
@section('content')

@include('compra._progress', ['step' => 3])

<div class="mx-auto max-w-xl px-4 py-16 sm:py-20">
    <div class="rounded-card border border-sala-700 bg-sala-900 p-6 sm:p-8"
         id="procesando"
         data-estado-url="{{ route('compra.estado', $pago) }}"
         data-paso2-url="{{ route('compra.step2') }}">
        <h1 class="display text-3xl text-texto" id="procesando-titulo" tabindex="-1">Confirmando tu pago…</h1>

        <p class="mt-4 text-texto-2" id="procesando-texto" role="status" aria-live="polite">
            Stripe nos está avisando del pago. Tarda unos segundos; no cierres esta página.
        </p>

        <dl class="mt-6 grid grid-cols-[auto,1fr] gap-x-4 gap-y-2 text-sm">
            <dt class="text-texto-2">Película</dt>
            <dd class="text-texto">{{ $pago->sesion->pelicula->titulo ?? 'CineFlow' }}</dd>
            <dt class="text-texto-2">Butacas</dt>
            <dd class="cifras font-semibold text-marquesina">{{ implode(', ', $pago->butacas) }}</dd>
            <dt class="text-texto-2">Total</dt>
            <dd class="cifras text-texto">{{ number_format($pago->totalEuros(), 2, ',', '.') }} €</dd>
        </dl>

        <div id="procesando-acciones" class="mt-6 hidden">
            <a href="{{ route('peliculas.index') }}" class="inline-flex min-h-11 items-center rounded-control border border-sala-700 px-4 font-semibold text-texto hover:bg-sala-850">Volver a la cartelera</a>
        </div>
    </div>
</div>

<script nonce="{{ Vite::cspNonce() }}">
(function () {
    const caja = document.getElementById('procesando');
    const texto = document.getElementById('procesando-texto');
    const titulo = document.getElementById('procesando-titulo');
    const acciones = document.getElementById('procesando-acciones');
    const limite = Date.now() + 60000;

    function final(tituloTexto, mensaje) {
        titulo.textContent = tituloTexto;
        texto.textContent = mensaje;
        acciones.classList.remove('hidden');
        titulo.focus();
    }

    async function consultar() {
        let datos = null;
        try {
            const res = await fetch(caja.dataset.estadoUrl, { headers: { Accept: 'application/json' } });
            if (res.ok) datos = await res.json();
        } catch (_) { /* red caída: se reintenta */ }

        if (datos) {
            if (datos.estado === 'pagado' && datos.reserva_url) {
                window.location.assign(datos.reserva_url);
                return;
            }
            if (datos.estado === 'reembolsado') {
                final('Pago devuelto', 'Alguien compró esas butacas un instante antes. Te hemos devuelto el dinero.');
                return;
            }
            if (datos.estado === 'caducado' || datos.estado === 'fallido') {
                texto.textContent = 'El pago no se completó. Te llevamos a elegir butacas de nuevo.';
                window.location.assign(caja.dataset.paso2Url);
                return;
            }
        }

        if (Date.now() < limite) {
            setTimeout(consultar, 2000);
        } else {
            final('Seguimos esperando a Stripe', 'El pago tarda más de lo normal. Si se completa, recibirás la entrada por correo y la verás en Mis entradas.');
        }
    }

    setTimeout(consultar, 1000);
})();
</script>
@endsection
