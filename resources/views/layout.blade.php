<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineFlow - @yield('title', 'Premium Cinema')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,900&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-container">
            <div class="top-bar-links">
                <a href="#">Promotions</a>
                <a href="#">Contact</a>
            </div>
            <div class="social-icons">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="header-premium">
        <div class="header-container">
            <!-- Logo -->
            <a href="/" style="text-decoration:none;display:flex;align-items:center;gap:0.25rem;">
                <span class="logo-cine">CINE</span>
                <span class="logo-lumiere">FLOW</span>
            </a>

            <!-- Main navigation -->
            <nav class="nav-main" style="gap:1.5rem;align-items:center;">
                <a href="{{ route('peliculas.index') }}" class="nav-link {{ Request::is('peliculas*') ? 'active' : '' }}">Now Showing</a>
                <a href="{{ route('cines.index') }}" class="nav-link {{ Request::is('cines*') ? 'active' : '' }}">Cinemas</a>

                @auth
                    @if(auth()->user()->canManage())
                        <a href="{{ route('taquilla.scanner') }}" class="nav-link {{ Request::is('taquilla*') ? 'active' : '' }}" style="font-size:0.8rem;"><i class="fas fa-qrcode" style="margin-right:5px;"></i>Validate</a>
                    @endif
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('salas.index') }}"    class="nav-link {{ Request::is('salas*')    ? 'active' : '' }}" style="opacity:0.6;font-size:0.8rem;">Screens</a>
                        <a href="{{ route('usuarios.index') }}" class="nav-link {{ Request::is('usuarios*') ? 'active' : '' }}" style="opacity:0.6;font-size:0.8rem;">Users</a>
                    @endif

                    <a href="{{ auth()->user()->canManage() ? route('dashboard') : route('cliente.dashboard') }}"
                       class="nav-link" style="color:#9ca3af;">{{ auth()->user()->name }}</a>
                    @if(auth()->user()->isAdmin())
                        <span style="color:var(--color-accent-bright);font-weight:700;font-size:0.7rem;letter-spacing:1px;text-transform:uppercase;">Admin</span>
                    @elseif(auth()->user()->isTaquilla())
                        <span style="color:#f0a500;font-weight:700;font-size:0.7rem;letter-spacing:1px;text-transform:uppercase;">Box Office</span>
                    @endif

                    @if(auth()->user()->rol === 'cliente')
                        <a href="{{ route('peliculas.index') }}" class="btn btn-primary" style="padding:0.5rem 1.25rem;font-size:0.8rem;">Buy Ticket</a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="nav-link" style="background:none;border:none;cursor:pointer;color:#9ca3af;padding:0;font-size:0.8rem;">Log Out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="nav-link {{ Request::is('login') ? 'active' : '' }}" style="color:#9ca3af;">Log In</a>
                    {{-- The button leads to the listings so guests can pick a movie and buy without registering --}}
                    <a href="{{ route('peliculas.index') }}" class="btn btn-primary" style="padding:0.5rem 1.25rem;font-size:0.8rem;">
                        <i class="fas fa-ticket-alt mr-1.5"></i>Buy Tickets
                    </a>
                @endauth
            </nav>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay">

        {{-- Panel deslizante --}}
        <div class="mobile-menu-panel" id="mobileMenuPanel">

            {{-- Header --}}
            <div class="mobile-menu-header">
                <a href="/" class="mobile-menu-logo">
                    <span class="logo-cine">CINE</span>
                    <span class="logo-lumiere">FLOW</span>
                </a>
                <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Close menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Main navigation --}}
            <nav class="mobile-nav">

                <span class="mobile-nav-section-label">Discover</span>

                <a href="{{ route('peliculas.index') }}" class="mobile-nav-link">
                    <i class="fas fa-film mnl-icon"></i>Now Showing
                </a>
                <a href="{{ route('cines.index') }}" class="mobile-nav-link">
                    <i class="fas fa-map-marker-alt mnl-icon"></i>Cinemas
                </a>

                @auth
                    {{-- Buy ticket (customer) --}}
                    @if(auth()->user()->rol === 'cliente')
                        <a href="{{ route('peliculas.index') }}" class="mobile-nav-link highlight">
                            <i class="fas fa-ticket-alt mnl-icon"></i>Buy Ticket
                        </a>
                    @endif

                    <div class="mobile-nav-divider"></div>
                    <span class="mobile-nav-section-label">My Account</span>

                    <a href="{{ auth()->user()->canManage() ? route('dashboard') : route('cliente.dashboard') }}" class="mobile-nav-link">
                        <i class="fas fa-th-large mnl-icon"></i>Dashboard
                    </a>
                    <a href="{{ route('profile.edit') }}" class="mobile-nav-link">
                        <i class="fas fa-user-circle mnl-icon"></i>Profile
                    </a>

                    @if(auth()->user()->canManage())
                        <div class="mobile-nav-divider"></div>
                        <span class="mobile-nav-section-label">Box Office</span>
                        <a href="{{ route('taquilla.scanner') }}" class="mobile-nav-link">
                            <i class="fas fa-qrcode mnl-icon"></i>Validate tickets
                        </a>
                        <a href="{{ route('reservas.index') }}" class="mobile-nav-link">
                            <i class="fas fa-ticket-alt mnl-icon"></i>Bookings
                        </a>
                    @endif

                    @if(auth()->user()->isAdmin())
                        <div class="mobile-nav-divider"></div>
                        <span class="mobile-nav-section-label">Administration</span>
                        <a href="{{ route('salas.index') }}" class="mobile-nav-link">
                            <i class="fas fa-door-open mnl-icon"></i>Screens
                        </a>
                        <a href="{{ route('usuarios.index') }}" class="mobile-nav-link">
                            <i class="fas fa-users mnl-icon"></i>Users
                        </a>
                    @endif

                    <div class="mobile-nav-divider"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="mobile-nav-link mobile-nav-link-btn" style="color:rgba(255,100,100,0.75);">
                            <i class="fas fa-sign-out-alt mnl-icon" style="color:rgba(255,100,100,0.5);"></i>Log Out
                        </button>
                    </form>

                @else
                    <div class="mobile-nav-divider"></div>
                    <span class="mobile-nav-section-label">Access</span>
                    <a href="{{ route('login') }}" class="mobile-nav-link">
                        <i class="fas fa-sign-in-alt mnl-icon"></i>Log In
                    </a>
                    <a href="{{ route('register') }}" class="mobile-nav-link">
                        <i class="fas fa-user-plus mnl-icon"></i>Create Account
                    </a>
                    <a href="{{ route('peliculas.index') }}" class="mobile-nav-link highlight">
                        <i class="fas fa-ticket-alt mnl-icon"></i>Buy Tickets
                    </a>
                @endauth

            </nav>

            {{-- Footer with user info --}}
            @auth
            <div class="mobile-menu-footer">
                <div class="mobile-menu-user">
                    <div class="mobile-menu-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="mobile-menu-user-info">
                        <div class="mobile-menu-user-name">{{ auth()->user()->name }} {{ auth()->user()->apellidos }}</div>
                        <div class="mobile-menu-user-role">{{ ucfirst(auth()->user()->rol) }}</div>
                    </div>
                </div>
            </div>
            @endauth

        </div>{{-- /panel --}}
    </div>{{-- /overlay --}}

    <main>
        @if(session('status'))
            <div class="container">
                <div class="alert alert-success">{{ session('status') }}</div>
            </div>
        @endif

        @yield('content')
    </main>

    <script>
        const mobileMenuToggle  = document.getElementById('mobileMenuToggle');
        const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
        const mobileMenuClose   = document.getElementById('mobileMenuClose');
        const mobileMenuPanel   = document.getElementById('mobileMenuPanel');

        function openMenu() {
            mobileMenuOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeMenu() {
            mobileMenuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        mobileMenuToggle?.addEventListener('click', openMenu);
        mobileMenuClose?.addEventListener('click', closeMenu);

        // Close when clicking the dark backdrop (outside the panel)
        mobileMenuOverlay?.addEventListener('click', (e) => {
            if (!mobileMenuPanel?.contains(e.target)) closeMenu();
        });

        // Close with ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMenu();
        });

        /* Global scroll reveals (IntersectionObserver)
           Available on every page: any element with .reveal,
           .reveal-stagger or .section-title-accent animates in when it appears.
           Only transform/opacity (GPU). Respects prefers-reduced-motion. */
        (function () {
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const revealEls = document.querySelectorAll(
                '.reveal:not(.is-visible), .reveal-stagger:not(.is-visible), .section-title-accent:not(.is-visible)'
            );
            if (reduce || !('IntersectionObserver' in window)) {
                revealEls.forEach(el => el.classList.add('is-visible'));
                return;
            }
            const io = new IntersectionObserver((entries) => {
                for (const e of entries) {
                    if (e.isIntersecting) {
                        e.target.classList.add('is-visible');
                        io.unobserve(e.target);
                    }
                }
            }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
            revealEls.forEach(el => io.observe(el));
        }());
    </script>
</body>
</html>
