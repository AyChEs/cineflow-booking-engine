// Portada: entrada del hero (bandas, marquesina, haz), zoom y paralaje con el scroll y, en escritorio,
// la escena fijada en la que el hero se encoge hasta ser el primer fotograma de la tira de celuloide.

export function iniciarPortada({ gsap, ScrollTrigger, SplitText }) {
    const raiz = document.documentElement;
    const hero = document.querySelector('[data-hero]');
    if (!hero) return;

    entradaDelHero({ gsap, SplitText, hero });

    const mm = gsap.matchMedia();

    // Escritorio con ratón: escena fijada. La clase la pone el script inline del layout.
    mm.add('(min-width: 1024px) and (pointer: fine)', () => {
        if (!raiz.classList.contains('mov-escena')) return paralaje({ gsap, ScrollTrigger, hero });
        return escenaFijada({ gsap, ScrollTrigger, hero });
    });

    // Resto: el hero se queda donde está y solo hace zoom y paralaje al salir.
    mm.add('(max-width: 1023px), (pointer: coarse)', () => paralaje({ gsap, ScrollTrigger, hero }));
}

function entradaDelHero({ gsap, SplitText, hero }) {
    const titulo = hero.querySelector('[data-titulo]');
    const sup = hero.querySelector('.hero-banda--sup');
    const inf = hero.querySelector('.hero-banda--inf');
    const bombillas = hero.querySelector('.hero-bombillas');
    const detalles = hero.querySelectorAll('[data-hero-detalle]');
    const media = hero.querySelector('[data-hero-media] img');

    const division = SplitText.create(titulo, { type: 'chars', charsClass: 'letra', aria: 'auto' });
    gsap.set(division.chars, { opacity: 0.07 });
    gsap.set(bombillas, { opacity: 0 });
    gsap.set(detalles, { opacity: 0, y: 22 });
    gsap.set(media, { scale: 1.16, transformOrigin: '60% 40%' });
    document.documentElement.classList.add('mov-listo');

    const tl = gsap.timeline({ delay: 0.15 });
    // La sala se abre: las dos bandas panorámicas se retiran.
    tl.to(sup, { yPercent: -100, duration: 1.15, ease: 'power3.inOut' }, 0.1)
      .to(inf, { yPercent: 100, duration: 1.15, ease: 'power3.inOut' }, 0.1)
      .to(media, { scale: 1, duration: 2.4, ease: 'expo.out' }, 0.1)
      // La marquesina se enciende letra a letra, con parpadeo.
      .to(bombillas, { opacity: 1, duration: 0.4 }, 0.55)
      .to(division.chars, {
          keyframes: { opacity: [0.07, 1, 0.3, 1, 0.65, 1] },
          duration: 0.55,
          ease: 'none',
          stagger: { each: 0.055, from: 'start' },
      }, 0.65)
      .to(detalles, { opacity: 1, y: 0, duration: 0.9, ease: 'expo.out', stagger: 0.1 }, 1.35);
}

// Sin escena fijada: al salir de la portada, la imagen se acerca y sube más despacio que la página.
function paralaje({ gsap, ScrollTrigger, hero }) {
    const media = hero.querySelector('[data-hero-media]');
    const contenido = hero.querySelector('[data-hero-contenido]');
    const haz = hero.querySelector('.hero-haz');
    const t = gsap.timeline({
        defaults: { ease: 'none' },
        scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: 0.6 },
    });
    t.to(media, { scale: 1.2, yPercent: 7 }, 0)
     .to(contenido, { yPercent: -14, opacity: 0.2 }, 0)
     .to(haz, { yPercent: -12 }, 0);
    return () => t.scrollTrigger?.kill();
}

function escenaFijada({ gsap, ScrollTrigger, hero }) {
    const pista = document.querySelector('[data-escena]');
    const film = pista.querySelector('[data-film]');
    const banda = pista.querySelector('[data-film-banda]');
    const cabecera = pista.querySelector('[data-tira-cabecera]');
    const progreso = pista.querySelector('[data-film-progreso]');
    const barra = progreso?.querySelector('i');
    const enlace = hero.querySelector('[data-hero-enlace]');
    const media = hero.querySelector('[data-hero-media]');
    const contenido = hero.querySelector('[data-hero-contenido]');
    const detalles = hero.querySelectorAll('[data-hero-detalle]');
    const haz = hero.querySelector('.hero-haz');
    const cabeceraSitio = document.querySelector('.site-header');

    let tl;
    let temporizador;

    const construir = () => {
        if (tl) {
            tl.scrollTrigger?.kill();
            tl.kill();
            gsap.set([film, hero, banda, cabecera, progreso, barra, media, haz], { clearProps: 'transform,opacity' });
        }

        const alto = pista.clientHeight;
        const ancho = pista.clientWidth;
        const k = parseFloat(getComputedStyle(film).getPropertyValue('--k')) || 0.6;
        const margen = 64; // el primer fotograma no pega al borde de la ventana
        const recorrido = Math.max(0, film.offsetWidth - ancho + margen);
        // 1 unidad de la línea de tiempo = el alto de la escena en píxeles de scroll
        const u = alto;
        const d1 = 0.75 * u;
        const d2 = 1.05 * u;
        const d3 = Math.max(0.8 * u, recorrido * 0.9);

        tl = gsap.timeline({
            defaults: { ease: 'none' },
            scrollTrigger: {
                trigger: pista,
                start: () => `top ${cabeceraSitio?.offsetHeight ?? 64}px`,
                end: () => `+=${d1 + d2 + d3}`,
                pin: true,
                scrub: 0.7,
                anticipatePin: 1,
                invalidateOnRefresh: true,
            },
        });

        // 1 · Zoom y paralaje con el hero a sangre
        tl.to(media, { scale: 1.2, yPercent: 5, duration: d1 }, 0)
          .to(haz, { yPercent: -10, duration: d1 }, 0)
          .to(contenido, { yPercent: -6, duration: d1 }, 0);

        // 2 · El hero se encoge hasta ser el primer fotograma. Los detalles se apagan y la tira se enciende.
        const t2 = d1;
        tl.to(hero, { scale: k, duration: d2, ease: 'power2.inOut' }, t2)
          .to(film, { x: margen, duration: d2, ease: 'power2.inOut' }, t2)
          .to(media, { scale: 1.02, yPercent: 0, duration: d2, ease: 'power2.inOut' }, t2)
          .to(detalles, { opacity: 0, duration: d2 * 0.3 }, t2)
          .to(banda, { opacity: 1, duration: d2 * 0.55 }, t2 + d2 * 0.4)
          .fromTo(cabecera, { opacity: 0, y: 24 }, { opacity: 1, y: 0, duration: d2 * 0.5 }, t2 + d2 * 0.5)
          .to(progreso, { opacity: 1, duration: d2 * 0.4 }, t2 + d2 * 0.6)
          .set(enlace, { pointerEvents: 'auto' }, t2 + d2 * 0.9);

        // 3 · La tira pasa como película
        const t3 = d1 + d2;
        tl.to(film, { x: -(film.offsetWidth - ancho), duration: d3 }, t3)
          .to(barra, { scaleX: 1, duration: d3 }, t3);

        // Con el hero encogido, el texto que queda al alcance es el título; el resto no recibe foco.
        tl.set(contenido.querySelectorAll('a, button'), { visibility: 'hidden' }, t2 + d2 * 0.3);
        return tl;
    };

    construir();

    // Si cambia el ancho de la ventana se recalcula todo (el alto lo cubre invalidateOnRefresh).
    let anchoAnterior = window.innerWidth;
    const alRedimensionar = () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(() => {
            if (window.innerWidth === anchoAnterior) return;
            anchoAnterior = window.innerWidth;
            construir();
            ScrollTrigger.refresh();
        }, 250);
    };
    window.addEventListener('resize', alRedimensionar);

    return () => {
        window.removeEventListener('resize', alRedimensionar);
        tl?.scrollTrigger?.kill();
        tl?.kill();
        gsap.set([film, hero, banda, cabecera, progreso, barra, media, contenido, haz, enlace], { clearProps: 'all' });
    };
}
