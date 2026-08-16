@extends('layout')

@section('title', 'Cinema Premium')

@section('content')

{{-- ======================================================
     Hero Section — Most popular movie at the Spanish box office
     If $tmdbHero has data (movie not in the local DB),
     we show its poster as background and its info.
     ====================================================== --}}
@php
    $heroTitle    = $destacada?->titulo    ?? ($tmdbHero['title']       ?? 'CINEMA PREMIUM');
    $heroSynopsis = $destacada?->sinopsis  ?? ($tmdbHero['description'] ?? "Live the unique cinema experience");
    $heroPoster   = $destacada?->poster_url ?? ($tmdbHero['image_url']  ?? '');
    $heroLink     = $destacada ? route('peliculas.show', $destacada) : route('peliculas.index');
    $heroTrailer  = $destacada?->trailer_url ?? null;
@endphp

<section class="hero-cinematic" id="heroCinematic">

    {{-- depth-0: poster a pantalla completa (parallax) --}}
    <div class="hc-layer hc-poster" data-depth="0" data-parallax="0.15" aria-hidden="true"
         @if($heroPoster) style="background-image: url('{{ $heroPoster }}');" @endif></div>

    {{-- depth-1: readability gradient + atmospheric light blobs --}}
    <div class="hc-layer hc-scrim" data-depth="1" aria-hidden="true"></div>
    <div class="hc-glow hc-glow-a" data-depth="1" data-parallax="0.30" aria-hidden="true"></div>
    <div class="hc-glow hc-glow-b" data-depth="1" data-parallax="0.30" aria-hidden="true"></div>

    {{-- depth-5: film grain --}}
    <div class="hc-grain" data-depth="5" aria-hidden="true"></div>

    {{-- depth-4: contenido --}}
    <div class="hero-content">
        <span class="hc-badge hc-enter hc-enter-1">Now Showing</span>
        <h1 class="hero-title hc-enter hc-enter-2" id="heroTitle">{{ strtoupper($heroTitle) }}</h1>
        <p class="hero-subtitle hc-enter hc-enter-3" id="heroSubtitle">{{ Str::limit($heroSynopsis, 160) }}</p>

        <div id="heroTags" class="flex flex-wrap gap-2 mb-6 hc-enter hc-enter-3">
            @if($destacada)
                @foreach($destacada->categorias->take(3) as $cat)
                    <span class="px-4 py-1 rounded-full text-xs font-black uppercase tracking-widest
                                 bg-cinema-accent/20 text-cinema-accent border border-cinema-accent/40">
                        {{ $cat->nombre }}
                    </span>
                @endforeach
                @if($destacada->duracion_min)
                    <span class="px-4 py-1 rounded-full text-xs font-semibold
                                 bg-white/5 text-cinema-muted border border-white/10">
                        {{ $destacada->duracion_min }} min
                    </span>
                @endif
            @elseif($tmdbHero ?? null)
                @foreach(array_slice(explode(',', $tmdbHero['genre'] ?? ''), 0, 3) as $g)
                    @if(trim($g))
                        <span class="px-4 py-1 rounded-full text-xs font-black uppercase tracking-widest
                                     bg-cinema-accent/20 text-cinema-accent border border-cinema-accent/40">
                            {{ trim($g) }}
                        </span>
                    @endif
                @endforeach
                @if($tmdbHero['year'] ?? null)
                    <span class="px-4 py-1 rounded-full text-xs font-semibold
                                 bg-white/5 text-cinema-muted border border-white/10">
                        {{ $tmdbHero['year'] }}
                    </span>
                @endif
                @if($tmdbHero['stars'] ?? null)
                    <span class="px-4 py-1 rounded-full text-xs font-semibold
                                 bg-yellow-400/10 text-yellow-400 border border-yellow-400/20">
                        <i class="fas fa-star mr-1"></i>{{ number_format($tmdbHero['stars'], 1) }}/5
                    </span>
                @endif
            @endif
        </div>

        <div class="hero-buttons hc-enter hc-enter-4" id="heroButtons">
            <a href="{{ $heroLink }}" class="btn btn-primary btn-lg btn-shine">
                <i class="fas fa-ticket-alt mr-2"></i>Buy Tickets
            </a>
            @if($heroTrailer)
                <a href="{{ $heroTrailer }}" target="_blank" rel="noopener" class="btn btn-secondary btn-lg">
                    <i class="fas fa-play mr-2"></i>Watch Trailer
                </a>
            @endif
        </div>
    </div>

    <div class="hc-scroll-cue" aria-hidden="true">
        <span>Discover</span>
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

{{-- ======================================================
     Now Showing Section — Carousel
     ====================================================== --}}
<section class="container reveal" style="padding-top:3rem;padding-bottom:1rem;">
    <div class="section-header">
        <div>
            <h2 class="section-title section-title-accent">NOW SHOWING</h2>
            <p class="text-[color:var(--color-text-secondary)] text-sm mt-1">The best movies now showing</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('peliculas.index') }}" class="btn btn-secondary btn-sm">See all →</a>
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('peliculas.external.index') }}" class="btn btn-secondary btn-sm"
                       title="TMDB catalog — admin only">
                        <i class="fas fa-satellite-dish mr-1"></i>TMDB
                    </a>
                @endif
            @endauth
        </div>
    </div>

    {{-- Carousel --}}
    <style>
    .carousel-wrapper {
        position: relative;
    }
    .carousel-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 10;
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid rgba(255,255,255,0.15);
        background: rgba(0,0,0,0.75);
        color: white;
        font-size: 1.25rem;
        cursor: pointer;
        transition: background 0.2s, opacity 0.2s;
        backdrop-filter: blur(4px);
    }
    .carousel-btn:hover { background: var(--color-accent-bright); }
    .carousel-btn.prev { left: 0; }
    .carousel-btn.next { right: 0; }
    .carousel-btn[disabled] { opacity: 0.25; cursor: default; pointer-events: none; }

    .carousel-viewport {
        overflow: hidden;
        margin: 0 3rem; /* space for the buttons */
    }
    @media (max-width: 640px) {
        .carousel-viewport { margin: 0 2.5rem; }
        .carousel-btn { width: 2.25rem; height: 2.25rem; font-size: 1rem; }
    }

    #carouselTrack {
        display: flex;
        transition: transform 0.4s ease;
        will-change: transform;
    }

    /* Cada card ocupa exactamente 1/visible items del viewport */
    .card-movie-carousel {
        flex-shrink: 0;
        /* ancho se calcula en JS via CSS custom property */
    }
    </style>

    <div class="carousel-wrapper">
        <button id="carouselPrev" class="carousel-btn prev" onclick="moveCarousel(-1)" aria-label="Previous" disabled>&#8249;</button>

        <div class="carousel-viewport" id="carouselViewport">
            <div id="carouselTrack">
                @forelse($peliculas as $pelicula)
                    <div class="card-movie card-movie-carousel">
                        <div class="card-movie-poster">
                            <img src="{{ $pelicula->poster_url }}"
                                 alt="Poster de {{ $pelicula->titulo }}"
                                 loading="lazy">

                            <div class="card-movie-overlay">
                                <div class="text-center px-4 w-full">
                                    <a href="{{ route('peliculas.show', $pelicula) }}"
                                       class="btn btn-primary btn-sm" style="width:100%;display:block;">View</a>
                                </div>
                            </div>

                            @if($pelicula->categorias->first())
                                <div class="card-movie-badge">{{ $pelicula->categorias->first()->nombre }}</div>
                            @endif
                        </div>

                        <div class="card-movie-body">
                            <div class="card-movie-title">{{ $pelicula->titulo }}</div>
                            <div class="card-movie-meta">
                                @if($pelicula->rating)
                                    <span class="cm-rating"><i class="fas fa-star"></i>{{ number_format($pelicula->rating, 1) }}</span>
                                    <span class="cm-sep">·</span>
                                @endif
                                <span>{{ $pelicula->duracion_min ?? '?' }} min</span>
                                <span class="cm-sep">·</span>
                                <span>{{ $pelicula->classificacio_edad ?? 'TP' }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-[color:var(--color-text-secondary)]">No movies available.</p>
                @endforelse
            </div>
        </div>

        <button id="carouselNext" class="carousel-btn next" onclick="moveCarousel(1)" aria-label="Next">&#8250;</button>
    </div>

    <div id="carouselDots" class="flex justify-center gap-2 mt-5"></div>

    <script>
    (function(){
        const track    = document.getElementById('carouselTrack');
        const viewport = document.getElementById('carouselViewport');
        const dotsEl   = document.getElementById('carouselDots');
        const btnPrev  = document.getElementById('carouselPrev');
        const btnNext  = document.getElementById('carouselNext');
        const cards    = Array.from(track.children);
        const total    = cards.length;
        let current    = 0;

        function visibleCount() {
            const w = window.innerWidth;
            if (w < 480) return 2;
            if (w < 768) return 3;
            if (w < 1024) return 4;
            return 5;
        }

        function maxIndex() { return Math.max(0, total - visibleCount()); }

        function setCardWidths() {
            const vis  = visibleCount();
            const gap  = vis > 2 ? 16 : 12; // px — gap entre cards
            const vw   = viewport.clientWidth;
            const cardW = (vw - gap * (vis - 1)) / vis;
            cards.forEach(c => {
                c.style.width     = cardW + 'px';
                c.style.marginRight = gap + 'px';
            });
            // remove margin from the last card so it doesn't overflow
            if (cards[total - 1]) cards[total - 1].style.marginRight = '0';
        }

        function buildDots() {
            dotsEl.innerHTML = '';
            const pages = maxIndex() + 1;
            if (pages <= 1) return;
            for (let i = 0; i < pages; i++) {
                const d = document.createElement('button');
                d.style.cssText = `width:8px;height:8px;border-radius:50%;border:none;padding:0;cursor:pointer;
                    transition:background .2s,transform .2s;
                    background:${i === current ? 'var(--color-accent-bright)' : 'rgba(255,255,255,0.25)'};
                    transform:${i === current ? 'scale(1.35)' : 'scale(1)'};`;
                d.onclick = () => goto(i);
                dotsEl.appendChild(d);
            }
        }

        function goto(idx) {
            current = Math.max(0, Math.min(idx, maxIndex()));
            // calcula offset sumando anchos reales de cards + margins
            let offset = 0;
            for (let i = 0; i < current; i++) {
                const c = cards[i];
                offset += c.offsetWidth + parseInt(c.style.marginRight || 0);
            }
            track.style.transform = `translateX(-${offset}px)`;
            btnPrev.disabled = current === 0;
            btnNext.disabled = current >= maxIndex();
            buildDots();
        }

        function init() {
            setCardWidths();
            current = Math.min(current, maxIndex());
            goto(current);
        }

        window.moveCarousel = (dir) => goto(current + dir);
        window.addEventListener('resize', init);
        init();
    }());
    </script>
</section>

{{-- ======================================================
     Cinemas Section
     ====================================================== --}}
<section class="container reveal" style="padding-top:2rem;padding-bottom:4rem;">
    <div class="section-header">
        <div>
            <h2 class="section-title section-title-accent">OUR CINEMAS</h2>
            <p class="text-[color:var(--color-text-secondary)] text-sm mt-1">Find the cinema closest to you</p>
        </div>
        <a href="{{ route('cines.index') }}" class="btn btn-secondary btn-sm">See all →</a>
    </div>

    <div class="cines-grid reveal-stagger">
        @forelse($cines as $cine)
            <div class="card-cine">
                <a href="{{ route('cines.show', $cine) }}"
                   class="absolute inset-0 z-10 block" aria-label="{{ $cine->nombre }}"></a>

                <div class="w-full h-full flex items-center justify-center"
                     style="background: linear-gradient(135deg,#1a1a2e,#16213e,#0f3460);">
                    <i class="fas fa-film text-white/5 text-8xl"></i>
                </div>

                <div class="card-cine-overlay z-[2]">
                    <h3 class="card-cine-title">{{ $cine->nombre }}</h3>
                    <p class="card-cine-ciudad">{{ $cine->ciudad }}</p>
                    <p class="text-white/50 text-xs mt-1">
                        {{ $cine->salas_count }} {{ $cine->salas_count == 1 ? 'screen' : 'screens' }}
                    </p>
                </div>
            </div>
        @empty
            <p class="text-[color:var(--color-text-secondary)] col-span-full">No cinemas registered.</p>
        @endforelse
    </div>
</section>

{{-- ======================================================
     Motion engine — hero parallax + scroll reveals.
     Only transform/opacity (GPU). Fully disabled if the
     user has requested reduced-motion.
     ====================================================== --}}
<script>
(function(){
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce) return;

    const coarse = window.matchMedia('(pointer: coarse)').matches;

    /* Hero parallax (disabled on touch for performance) */
    const parallaxEls = Array.from(document.querySelectorAll('[data-parallax]'));
    if (parallaxEls.length && !coarse) {
        let ticking = false;
        const onScroll = () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(() => {
                const y = window.scrollY;
                for (const el of parallaxEls) {
                    const speed = parseFloat(el.dataset.parallax) || 0.15;
                    el.style.transform = (el.classList.contains('hc-poster') ? 'scale(1.12) ' : '')
                        + `translate3d(0, ${y * speed}px, 0)`;
                }
                ticking = false;
            });
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    /* Scroll reveals (IntersectionObserver) */
    const revealEls = document.querySelectorAll('.reveal, .reveal-stagger, .section-title-accent');
    const io = new IntersectionObserver((entries) => {
        for (const e of entries) {
            if (e.isIntersecting) {
                e.target.classList.add('is-visible');
                io.unobserve(e.target);
            }
        }
    }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });
    revealEls.forEach(el => io.observe(el));
}());
</script>

@endsection