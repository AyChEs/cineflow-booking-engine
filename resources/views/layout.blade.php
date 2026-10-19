<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0E0D10">
    <title>@hasSection('title')@yield('title') · CineFlow @else CineFlow · Taquilla de cine @endif</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col">
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
            <a href="{{ route('home') }}" class="logo" aria-label="CineFlow, ir a la portada">
                <span class="logo-cine">Cine</span><span class="logo-lumiere">Flow</span>
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
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p>CineFlow · Proyecto final de ciclo de Ayman Charoui, Ismael Achamrouk y Danna Guevara.</p>
            <p class="flex flex-col gap-1 sm:items-end">
                <span>Pagos en modo de prueba de Stripe: no se cobra nada.</span>
                <a href="https://github.com/AyChEs/cineflow-booking-engine">Código en GitHub</a>
            </p>
        </div>
    </footer>
</body>
</html>
