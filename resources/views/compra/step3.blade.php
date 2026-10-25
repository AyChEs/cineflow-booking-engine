@extends('layout')
@section('title', 'Pago')
@section('content')

@include('compra._progress', ['step' => 3])

@php
    $total = number_format($compra['total'], 2, ',', '.').' €';
    $metodo = old('metode', 'targeta');
    $modoTarjeta = old('card_mode', $savedCard ? 'saved' : 'new');
@endphp

<div class="mx-auto grid max-w-5xl items-start gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[1fr,20rem] lg:py-12">
    <div class="rounded-card border border-sala-700 bg-sala-900 p-4 sm:p-6">
        <h1 class="display text-3xl text-texto">Pago</h1>

        @if (session('error'))
            <div class="alert alert-error mt-5" role="alert"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />{{ session('error') }}</div>
        @endif
        <div id="fin-reserva" class="alert alert-error mt-5 focus:outline-none" tabindex="-1" hidden>
            <x-icon name="clock" class="mt-0.5 h-4 w-4 shrink-0" />
            <span>El tiempo de reserva ha terminado. <a href="{{ route('compra.step2') }}" class="font-semibold text-texto underline">Vuelve a elegir tus butacas</a>.</span>
        </div>

        <form method="POST" action="{{ $stripeEnabled ? route('compra.stripe.checkout') : route('compra.step3.store') }}" id="payForm" class="mt-6" novalidate>
            @csrf

            <fieldset>
                <legend class="mb-4 text-lg font-semibold text-texto">Tus datos</legend>
                <div class="grid gap-x-4 sm:grid-cols-2">
                    <x-campo name="nom" label="Nombre" :value="$user?->name" required autocomplete="name" />
                    <x-campo name="email" label="Correo electrónico" type="email" :value="$user?->email" required autocomplete="email" ayuda="Te mandamos aquí la entrada con el código QR." />
                </div>
            </fieldset>

            @if ($stripeEnabled)
                <div class="mt-4 rounded-card border border-sala-700 bg-sala-950 p-4 text-sm">
                    <p class="flex items-start gap-2 text-texto">
                        <x-icon name="lock" class="mt-0.5 h-4 w-4 shrink-0 text-texto-2" />
                        Pagarás en la página segura de Stripe y volverás aquí al terminar.
                    </p>
                    <p class="mt-2 text-texto-2">Modo de prueba: usa la tarjeta <strong class="cifras text-texto">4242 4242 4242 4242</strong>, cualquier fecha futura y cualquier CVC.</p>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-brillo mt-6 w-full sm:w-auto">
                    <x-icon name="lock" class="h-5 w-5" /><span>Pagar <span class="cifras">{{ $total }}</span> con Stripe</span>
                </button>
            @else
                <fieldset class="mt-4">
                    <legend class="mb-3 text-lg font-semibold text-texto">Forma de pago</legend>
                    <p class="mb-4 text-sm text-texto-2">Pago simulado: no se cobra nada.</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach (['targeta' => ['Tarjeta', 'card'], 'bizum' => ['Bizum', 'send']] as $valor => [$nombre, $icono])
                            <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-control border border-sala-700 px-4 has-[:checked]:border-carmin-400 has-[:checked]:bg-sala-850">
                                <input type="radio" name="metode" value="{{ $valor }}" @checked($metodo === $valor)
                                       class="h-5 w-5 border-libre bg-sala-900 text-carmin-600 focus:ring-foco focus:ring-offset-sala-900">
                                <x-icon :name="$icono" class="h-5 w-5 text-texto-2" /><span class="font-semibold text-texto">{{ $nombre }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('metode')<p class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />{{ $message }}</p>@enderror
                </fieldset>

                <div id="panel-targeta" class="mt-6" @if ($metodo !== 'targeta') hidden @endif>
                    @if ($savedCard)
                        <fieldset class="mb-4">
                            <legend class="mb-2 text-sm font-semibold text-texto">Tarjeta</legend>
                            <div class="flex flex-col gap-2">
                                <label class="flex min-h-11 items-center gap-3 text-texto"><input type="radio" name="card_mode" value="saved" @checked($modoTarjeta === 'saved') class="h-5 w-5 border-libre bg-sala-900 text-carmin-600 focus:ring-foco focus:ring-offset-sala-900">Usar la guardada <span class="cifras text-texto-2">{{ $savedCard }}</span></label>
                                <label class="flex min-h-11 items-center gap-3 text-texto"><input type="radio" name="card_mode" value="new" @checked($modoTarjeta === 'new') class="h-5 w-5 border-libre bg-sala-900 text-carmin-600 focus:ring-foco focus:ring-offset-sala-900">Usar otra tarjeta</label>
                            </div>
                        </fieldset>
                    @endif
                    <div id="tarjeta-nueva" @if ($savedCard && $modoTarjeta === 'saved') hidden @endif>
                        <x-campo name="num_targeta" label="Número de tarjeta" inputmode="numeric" autocomplete="cc-number" maxlength="23" ayuda="Prueba con 4242 4242 4242 4242." />
                        <x-campo name="titular_targeta" label="Titular" autocomplete="cc-name" />
                        <div class="grid grid-cols-2 gap-x-4">
                            <x-campo name="expiry_targeta" label="Caducidad (MM/AA)" inputmode="numeric" autocomplete="cc-exp" maxlength="5" />
                            <x-campo name="cvv_targeta" label="CVC" inputmode="numeric" autocomplete="cc-csc" maxlength="4" />
                        </div>
                        @auth
                            <label class="mb-2 flex min-h-11 items-center gap-3 text-sm text-texto-2">
                                <input type="checkbox" name="guardar_targeta" value="1" @checked(old('guardar_targeta'))
                                       class="h-5 w-5 rounded border-libre bg-sala-900 text-carmin-600 focus:ring-foco focus:ring-offset-sala-900">
                                Guardar los últimos 4 números para la próxima vez
                            </label>
                        @endauth
                    </div>
                </div>

                <div id="panel-bizum" class="mt-6" @if ($metodo !== 'bizum') hidden @endif>
                    <x-campo name="telefon_bizum" label="Móvil de Bizum" type="tel" inputmode="numeric" autocomplete="tel-national" maxlength="9" ayuda="9 cifras, empieza por 6 o 7." />
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-brillo mt-4 w-full sm:w-auto">
                    <x-icon name="lock" class="h-5 w-5" /><span>Pagar <span class="cifras">{{ $total }}</span></span>
                </button>
            @endif
        </form>
    </div>

    @include('compra._sidebar', ['sesion' => $sesion, 'step' => 3, 'compra' => $compra, 'expiresAt' => $expiresAt ?? null])
</div>

@unless ($stripeEnabled)
<script>
(function () {
    const form = document.getElementById('payForm');
    const paneles = { targeta: document.getElementById('panel-targeta'), bizum: document.getElementById('panel-bizum') };
    const nueva = document.getElementById('tarjeta-nueva');

    form.querySelectorAll('input[name="metode"]').forEach((r) => r.addEventListener('change', () => {
        Object.entries(paneles).forEach(([k, p]) => { p.hidden = k !== r.value; });
    }));
    form.querySelectorAll('input[name="card_mode"]').forEach((r) => r.addEventListener('change', () => {
        nueva.hidden = r.value === 'saved';
    }));

    // Número de tarjeta en grupos de 4 y caducidad con barra, sin cambiar lo que se envía.
    const numero = document.getElementById('num_targeta');
    numero?.addEventListener('input', () => {
        numero.value = numero.value.replace(/\D/g, '').slice(0, 19).replace(/(.{4})/g, '$1 ').trim();
    });
    const caducidad = document.getElementById('expiry_targeta');
    caducidad?.addEventListener('input', () => {
        let v = caducidad.value.replace(/\D/g, '').slice(0, 4);
        if (v.length > 2) v = v.slice(0, 2) + '/' + v.slice(2);
        caducidad.value = v;
    });
})();
</script>
@endunless
@endsection
