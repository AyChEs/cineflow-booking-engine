@extends('layout')
@section('title', 'Elige tus entradas')
@section('content')

@include('compra._progress', ['step' => 1])

<div class="mx-auto grid max-w-5xl items-start gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[1fr,20rem] lg:py-12">
    <div class="rounded-card border border-sala-700 bg-sala-900 p-4 sm:p-6">
        <h1 class="display text-3xl text-texto">Elige tus entradas</h1>
        <p class="mt-2 text-texto-2">Hasta 10 entradas por compra. El precio depende del tipo de entrada.</p>

        @if (session('error'))
            <div class="alert alert-error mt-5" role="alert"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('compra.step1.store') }}" id="step1form" class="mt-6" novalidate>
            @csrf
            <input type="hidden" name="sesion_id" value="{{ $sesion->id }}">

            <ul class="divide-y divide-sala-700 rounded-card border border-sala-700">
                @foreach ($tipus as $key => $info)
                    @php $precio = \App\Http\Controllers\CompraController::precioCentimos((float) $sesion->preu_base, $key) / 100; @endphp
                    <li class="grid grid-cols-[1fr,auto] items-center gap-x-4 gap-y-3 px-4 py-4 sm:flex">
                        <div class="col-span-2 min-w-0 sm:flex-1">
                            <p class="font-semibold text-texto" id="tipo-{{ $key }}">{{ $info['label'] }}</p>
                            @if ($info['desc'])
                                <p class="text-sm text-texto-2">{{ $info['desc'] }}</p>
                            @endif
                        </div>
                        <p class="cifras font-semibold text-texto sm:w-20 sm:text-right">{{ number_format($precio, 2, ',', '.') }} €</p>
                        <div class="flex items-center gap-1" data-cantidad data-tipo="{{ $key }}" data-precio="{{ $precio }}">
                            <button type="button" class="btn btn-secondary h-11 w-11 px-0" data-paso="-1"
                                    aria-label="Quitar una entrada {{ $info['nombre'] }}">
                                <x-icon name="minus" class="h-4 w-4" />
                            </button>
                            <label for="qty_{{ $key }}" class="sr-only">Entradas {{ $info['nombre'] }}</label>
                            <input type="number" name="entrades[{{ $key }}]" id="qty_{{ $key }}"
                                   value="{{ old('entrades.'.$key, $entrades[$key] ?? 0) }}" min="0" max="10" inputmode="numeric"
                                   class="form-input cifras h-11 w-14 px-1 text-center">
                            <button type="button" class="btn btn-secondary h-11 w-11 px-0" data-paso="1"
                                    aria-label="Añadir una entrada {{ $info['nombre'] }}">
                                <x-icon name="plus" class="h-4 w-4" />
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                <p class="text-texto-2" aria-live="polite" id="resumen-entradas">0 entradas</p>
                <div class="flex flex-col items-end gap-1.5">
                    <button type="submit" id="btnSeguent" class="btn btn-primary btn-lg btn-brillo" aria-describedby="ayuda-continuar">
                        Continuar<x-icon name="arrow-right" class="h-5 w-5" />
                    </button>
                    <p id="ayuda-continuar" class="text-sm text-texto-2">Elige al menos una entrada</p>
                </div>
            </div>
        </form>
    </div>

    @include('compra._sidebar', ['sesion' => $sesion, 'step' => 1, 'compra' => null])
</div>

<script>
(function () {
    const form = document.getElementById('step1form');
    const boton = document.getElementById('btnSeguent');
    const ayuda = document.getElementById('ayuda-continuar');
    const resumen = document.getElementById('resumen-entradas');
    const total = document.getElementById('sidebarTotal');
    const controles = Array.from(form.querySelectorAll('[data-cantidad]'));
    const euros = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' });
    const MAX = 10;

    const campo = (c) => c.querySelector('input');
    const cantidad = (c) => Math.max(0, parseInt(campo(c).value, 10) || 0);
    const suma = () => controles.reduce((n, c) => n + cantidad(c), 0);

    function actualizar() {
        let importe = 0;
        controles.forEach((c) => { importe += cantidad(c) * parseFloat(c.dataset.precio); });
        const n = suma();
        resumen.textContent = n === 1 ? '1 entrada' : `${n} entradas`;
        if (total) total.textContent = euros.format(importe);
        const vale = n > 0 && n <= MAX;
        boton.setAttribute('aria-disabled', vale ? 'false' : 'true');
        ayuda.textContent = n === 0 ? 'Elige al menos una entrada' : (n > MAX ? 'Puedes comprar 10 entradas como máximo' : '');
        ayuda.hidden = vale;
        controles.forEach((c) => {
            c.querySelector('[data-paso="-1"]').disabled = cantidad(c) === 0;
            c.querySelector('[data-paso="1"]').disabled = n >= MAX;
        });
    }

    controles.forEach((c) => {
        c.querySelectorAll('[data-paso]').forEach((b) => b.addEventListener('click', () => {
            const paso = parseInt(b.dataset.paso, 10);
            if (paso > 0 && suma() >= MAX) return;
            campo(c).value = Math.max(0, cantidad(c) + paso);
            actualizar();
            // Si el botón queda deshabilitado, el foco pasa al campo para no perderse.
            if (b.disabled) campo(c).focus();
        }));
        campo(c).addEventListener('input', () => {
            const v = Math.min(MAX, cantidad(c));
            if (String(v) !== campo(c).value && campo(c).value !== '') campo(c).value = v;
            actualizar();
        });
    });

    form.addEventListener('submit', (e) => {
        if (boton.getAttribute('aria-disabled') === 'true') {
            e.preventDefault();
            ayuda.hidden = false;
            controles[0].querySelector('[data-paso="1"]').focus();
        }
    });

    actualizar();
})();
</script>
@endsection
