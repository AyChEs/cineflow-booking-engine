<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0E0D10">
    <meta name="description" content="@yield('description', 'CineFlow: cartelera, sesiones y compra de entradas con selección de butaca.')">
    <title>@hasSection('title')@yield('title') · {{ config('app.name') }} @else {{ config('app.name') }} · Taquilla de cine @endif</title>
    <link rel="icon" href="{{ asset('img/marca/favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('img/marca/apple-touch-icon.png') }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="@hasSection('title')@yield('title') · {{ config('app.name') }}@else{{ config('app.name') }} · Taquilla de cine @endif">
    <meta property="og:description" content="@yield('description', config('marca.lema'))">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ asset('img/marca/og.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    @stack('precarga')
    <link rel="preload" href="{{ Vite::asset('resources/fonts/big-shoulders-display-latin-wght.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @hasSection('mov')
        {{-- Movimiento (sesión 09): solo en las páginas públicas. La compra, el acceso y el panel no lo cargan. --}}
        @vite('resources/css/movimiento.css')
        <script nonce="{{ Vite::cspNonce() }}">
            (function () {
                var q = function (c) { return window.matchMedia(c).matches; };
                var h = document.documentElement;
                if (q('(prefers-reduced-motion: reduce)')) return;
                h.classList.add('mov-ok');
                @if (trim($__env->yieldContent('mov')) === 'portada')
                if (q('(min-width: 1024px) and (min-height: 560px) and (pointer: fine)')) h.classList.add('mov-escena');
                @endif
                // Si el movimiento no arranca, el contenido no se queda oculto.
                setTimeout(function () { if (!window.__mov) h.classList.remove('mov-ok', 'mov-escena'); }, 4500);
            })();
        </script>
    @endif
    @stack('head')
</head>
<body class="flex min-h-screen flex-col" @hasSection('mov') data-mov="@yield('mov')" @endif>
    <a href="#contenido" class="skip-link">Saltar al contenido</a>

    @php
        $usuario = auth()->user();
        $esPersonal = $usuario?->canManage() ?? false;
        $enlaces = [
            ['ruta' => 'peliculas.index', 'activo' => 'peliculas*', 'texto' => 'Cartelera', 'icono' => 'film'],
            ['ruta' => 'cines.index', 'activo' => 'cines*', 'texto' => 'Cines', 'icono' => 'map-pin'],
        ];
        if ($usuario && ! $esPersonal) {
            $enlaces[] = ['ruta' => 'reservas.mis', 'activo' => 'mis-reservas*', 'texto' => 'Mis entradas', 'icono' => 'ticket'];
        }
        if ($esPersonal) {
            $enlaces[] = ['ruta' => 'dashboard', 'activo' => 'dashboard', 'texto' => 'Panel', 'icono' => 'grid'];
            $enlaces[] = ['ruta' => 'taquilla.scanner', 'activo' => 'taquilla*', 'texto' => 'Validar entradas', 'icono' => 'qr'];
        }
    @endphp

    <header class="site-header">
        <div class="site-header-inner">
            <a href="{{ route('home') }}" class="logo" aria-label="{{ config('app.name') }}, ir a la portada">
                <x-marca.logo :animado="trim($__env->yieldContent('mov')) === 'portada'" />
            </a>

            <nav class="site-nav" aria-label="Principal">
                @foreach ($enlaces as $enlace)
                    <a href="{{ route($enlace['ruta']) }}" class="site-nav-link"
                       @if (request()->is($enlace['activo'])) aria-current="page" @endif>{{ $enlace['texto'] }}</a>
                @endforeach

                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="site-nav-link">Salir</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="site-nav-link"
                       @if (request()->is('login')) aria-current="page" @endif>Entrar</a>
                @endauth

                <a href="{{ route('peliculas.index') }}" class="btn btn-primary ml-2">
                    <x-icon name="ticket" class="h-4 w-4" />Comprar entradas
                </a>
            </nav>

            <button type="button" class="menu-toggle" id="menu-toggle"
                    aria-label="Abrir menú" aria-expanded="false" aria-controls="menu-movil">
                <x-icon name="menu" class="h-5 w-5" />
            </button>
        </div>
    </header>

    <div class="mobile-menu" id="menu-movil" hidden inert>
        <nav aria-label="Principal en móvil">
            <a href="{{ route('peliculas.index') }}" class="btn btn-primary btn-full btn-lg">
                <x-icon name="ticket" class="h-5 w-5" />Comprar entradas
            </a>

            <span class="mobile-menu-label">Descubre</span>
            @foreach ($enlaces as $enlace)
                <a href="{{ route($enlace['ruta']) }}" class="mobile-menu-link"
                   @if (request()->is($enlace['activo'])) aria-current="page" @endif>
                    <x-icon :name="$enlace['icono']" class="h-5 w-5 text-texto-2" />{{ $enlace['texto'] }}
                </a>
            @endforeach

            <span class="mobile-menu-label">Tu cuenta</span>
            @auth
                @unless ($esPersonal)
                    <a href="{{ route('cliente.dashboard') }}" class="mobile-menu-link">
                        <x-icon name="grid" class="h-5 w-5 text-texto-2" />Mi cuenta
                    </a>
                @endunless
                <a href="{{ route('profile.edit') }}" class="mobile-menu-link">
                    <x-icon name="user" class="h-5 w-5 text-texto-2" />Perfil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="mobile-menu-link">
                        <x-icon name="logout" class="h-5 w-5 text-texto-2" />Salir
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="mobile-menu-link">
                    <x-icon name="login" class="h-5 w-5 text-texto-2" />Entrar
                </a>
                <a href="{{ route('register') }}" class="mobile-menu-link">
                    <x-icon name="user-plus" class="h-5 w-5 text-texto-2" />Crear cuenta
                </a>
            @endauth
        </nav>
    </div>

    <main id="contenido" tabindex="-1" class="flex-1 focus:outline-none">
        @if (session('status') && ! request()->routeIs('login', 'password.request', 'verification.notice'))
            <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6">
                <div class="alert alert-success" role="status">
                    <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" />{{ session('status') }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 pt-seccion sm:px-6 md:grid-cols-[1.4fr,1fr,1fr]">
            <div>
                <x-marca.logo />
                <p class="pie-lema mt-6">{{ config('marca.lema') }}</p>
            </div>
            <nav aria-label="Pie">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-texto-2">Taquilla</h2>
                <ul class="space-y-2">
                    <li><a href="{{ route('peliculas.index') }}">Cartelera</a></li>
                    <li><a href="{{ route('cines.index') }}">Cines</a></li>
                    <li><a href="{{ route('marca') }}">Marca</a></li>
                </ul>
            </nav>
            <div class="space-y-2">
                <p>Proyecto final de ciclo de Ayman Charoui, Ismael Achamrouk y Danna Guevara.</p>
                <p>Pagos en modo de prueba de Stripe: no se cobra nada.</p>
                <p><a href="https://github.com/AyChEs/cineflow-booking-engine">Código en GitHub</a></p>
            </div>
        </div>
        {{-- El logotipo a todo el ancho. Decorativo para lectores de pantalla (el nombre ya está arriba). --}}
        <div class="pie-logo" data-pie-logo>
            <x-marca.logo-ancho decorativo />
        </div>
    </footer>
</body>
</html>
