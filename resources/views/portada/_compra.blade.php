{{-- «Cómo se compra»: cuatro pasos. En escritorio se fija y avanza con el scroll; en móvil y con movimiento reducido son cuatro tarjetas con su estado final. --}}
@php
    $titulo = $destacada?->titulo ?? ($tmdbHero['title'] ?? 'Tu película');
    $pasos = [
        ['titulo' => 'Elige la sesión y la butaca', 'texto' => 'El mapa muestra qué está libre, qué está retenido y qué se ha vendido. Cada estado se distingue por forma y texto, no solo por color.'],
        ['titulo' => 'Tu butaca queda guardada 8 minutos', 'texto' => 'Al elegirla se bloquea para ti. Nadie más puede cogerla mientras terminas la compra.'],
        ['titulo' => 'Pagas con Stripe', 'texto' => 'Pago en modo de prueba: no se cobra nada. La reserva se confirma cuando Stripe avisa de que el pago ha ido bien.'],
        ['titulo' => 'Recibes tu entrada con QR', 'texto' => 'La tienes en Mis entradas y en tu correo. El código va firmado y la taquilla lo valida a la entrada.'],
    ];
@endphp
<section class="compra" id="como-se-compra" aria-labelledby="como-se-compra-titulo" data-compra>
    {{-- Símbolos de las butacas ilustradas: los mismos cuatro estados del mapa real. --}}
    <svg width="0" height="0" class="absolute" aria-hidden="true" focusable="false">
        <symbol id="bt-libre" viewBox="0 0 28 28"><path d="M2 10a8 8 0 0 1 8-8h8a8 8 0 0 1 8 8v14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2z" fill="none" stroke="#8C8794" stroke-width="2.4"/></symbol>
        <symbol id="bt-tuya" viewBox="0 0 28 28"><path d="M2 10a8 8 0 0 1 8-8h8a8 8 0 0 1 8 8v14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2z" fill="#EBD67C" stroke="#EBD67C" stroke-width="2.4"/><path d="m8.5 14 3.8 3.8 7.2-7.6" fill="none" stroke="#0E0D10" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
        <symbol id="bt-vendida" viewBox="0 0 28 28"><path d="M2 10a8 8 0 0 1 8-8h8a8 8 0 0 1 8 8v14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2z" fill="#57525F"/><path d="m9.5 9.5 9 9m0-9-9 9" stroke="#E4E0EA" stroke-width="2.4" stroke-linecap="round"/></symbol>
        <symbol id="bt-retenida" viewBox="0 0 28 28"><path d="M2 10a8 8 0 0 1 8-8h8a8 8 0 0 1 8 8v14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2z" fill="#3A3641" stroke="#8C8794" stroke-width="2.2" stroke-dasharray="4 3"/><rect x="9" y="13" width="10" height="7" rx="1.5" fill="#E4E0EA"/><path d="M11 13v-2a3 3 0 0 1 6 0v2" fill="none" stroke="#E4E0EA" stroke-width="2"/></symbol>
    </svg>

    {{-- .compra-pista es el elemento que GSAP fija: va a todo el ancho y sin márgenes automáticos (si los lleva, al fijarse de golpe
         el bloque se pega al borde izquierdo). El contenedor centrado es .compra-rejilla, el mismo ancho que la cabecera. --}}
    <div class="compra-pista" data-compra-pista>
      <div class="compra-rejilla">
        <div class="compra-texto">
            <h2 id="como-se-compra-titulo" class="display text-4xl text-texto md:text-6xl">Cómo se compra</h2>
            <p class="mt-3 max-w-md text-texto-2">Eliges butaca, la guardamos 8 minutos, pagas y recibes la entrada con QR.</p>
            <ol class="compra-pasos" data-pasos>
                @foreach ($pasos as $i => $paso)
                    <li class="compra-paso" data-paso="{{ $i }}" @if ($i === 0) data-activo @endif>
                        <span class="compra-paso-num cifras" aria-hidden="true">{{ $i + 1 }}</span>
                        <div>
                            <h3 class="compra-paso-titulo">{{ $paso['titulo'] }}</h3>
                            <p class="compra-paso-texto">{{ $paso['texto'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
            <a href="{{ route('peliculas.index') }}" class="btn btn-primary btn-lg mt-8" data-magnetico>
                <x-icon name="ticket" class="h-5 w-5" />Elegir película
            </a>
        </div>

        {{-- Ilustraciones: decorativas, el texto de los pasos ya lo cuenta todo. --}}
        <div class="compra-escena" data-escena-compra aria-hidden="true">
            <div class="compra-vista" data-vista="0">
                <div class="compra-marco">
                    <p class="compra-marco-cabecera"><span>Elige tus butacas</span><span class="cifras">Sala 3 · 22:15</span></p>
                    @include('portada._mapa')
                </div>
            </div>

            <div class="compra-vista" data-vista="1">
                <div class="compra-marco">
                    <figure class="compra-foto">
                        <x-marca.imagen nombre="butaca" sizes="(min-width: 1024px) 32rem, 90vw" class="compra-foto-img" />
                        <figcaption class="compra-foto-pie"><span>Fila D, butaca 5</span><span class="compra-cuenta cifras" data-cuenta><x-icon name="clock" class="h-4 w-4" /><b data-cuenta-texto>8:00</b></span></figcaption>
                    </figure>
                    @include('portada._mapa', ['tuya' => true])
                    <p class="compra-marco-pie"><span class="compra-barra"><i data-cuenta-barra></i></span>La butaca es tuya hasta que se acabe el tiempo.</p>
                </div>
            </div>

            <div class="compra-vista" data-vista="2">
                <div class="compra-marco compra-pago">
                    <p class="compra-marco-cabecera"><span>Pago</span><span class="cifras">Modo de prueba</span></p>
                    <dl class="compra-resumen">
                        <div><dt>{{ $titulo }}</dt><dd class="cifras">Sala 3 · 22:15</dd></div>
                        <div><dt>Butaca D5, adulto</dt><dd class="cifras">9,50 €</dd></div>
                    </dl>
                    <div class="compra-campo"><span>Tarjeta</span><b class="cifras">4242 4242 4242 4242</b></div>
                    <div class="compra-campos">
                        <div class="compra-campo"><span>Caducidad</span><b class="cifras">12 / 34</b></div>
                        <div class="compra-campo"><span>CVC</span><b class="cifras">123</b></div>
                    </div>
                    <span class="compra-pagar cifras">Pagar 9,50 €</span>
                </div>
            </div>

            <div class="compra-vista" data-vista="3">
                <div class="compra-impresora">
                    <span class="compra-ranura"></span>
                    <div class="compra-hueco">
                        <div class="entrada" data-entrada>
                            <div class="entrada-cuerpo">
                                <p class="entrada-marca"><x-marca.simbolo class="h-5 w-5" /> {{ config('app.name') }}</p>
                                <p class="entrada-titulo">{{ $titulo }}</p>
                                <dl class="entrada-datos cifras">
                                    <div><dt>Sala</dt><dd>3</dd></div>
                                    <div><dt>Fila</dt><dd>D</dd></div>
                                    <div><dt>Butaca</dt><dd>5</dd></div>
                                    <div><dt>Hora</dt><dd>22:15</dd></div>
                                </dl>
                            </div>
                            <div class="entrada-talon">
                                <x-marca.qr-decorativo class="entrada-qr" />
                                <span class="cifras">Adulto</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sesión 11: en escritorio con movimiento, una sola ilustración de Rive (rive/compra) sustituye a los pasos 2 a 4.
                 La cuenta atrás es HTML sobre el lienzo; si el runtime no carga, siguen las tres vistas de arriba. --}}
            <div class="compra-vista compra-vista--rive" data-vista-rive data-rive-src="{{ asset('rive/compra.riv') }}" hidden>
                <div class="compra-marco compra-marco--rive">
                    <canvas class="compra-lienzo" tabindex="-1"></canvas>
                    <span class="compra-cuenta compra-cuenta--rive cifras" data-cuenta-rive><x-icon name="clock" class="h-4 w-4" /><b data-cuenta-rive-texto>8:00</b></span>
                </div>
            </div>
        </div>
      </div>
    </div>
</section>
