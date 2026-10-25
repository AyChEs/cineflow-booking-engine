// «Cómo se compra»: cuatro pasos. En escritorio la escena se fija y el scroll recorre los pasos:
// 1 el mapa se dibuja, 2 una butaca se ilumina y arranca la cuenta atrás de 8:00, 3 entra el pago y
// 4 la entrada troquelada sale como si se imprimiera. En móvil cada ilustración se anima al entrar.
// Sesión 11: en escritorio, los pasos 2 a 4 los pinta una ilustración de Rive (rive/compra) cuya entrada
// numérica «progreso» sigue a esta línea de tiempo. Hasta que carga, y si falla, siguen las vistas HTML.

export function iniciarCompra({ gsap, ScrollTrigger }) {
    const seccion = document.querySelector('[data-compra]');
    if (!seccion) return;
    const raiz = document.documentElement;
    const vistas = [...seccion.querySelectorAll('[data-vista]')];
    const pasos = [...seccion.querySelectorAll('[data-paso]')];
    const [v0, v1, v2, v3] = vistas;

    const mm = gsap.matchMedia();

    mm.add('(min-width: 1024px) and (pointer: fine)', () => {
        if (!raiz.classList.contains('mov-escena')) return estatica();
        return fijada();
    });
    mm.add('(max-width: 1023px), (pointer: coarse)', () => estatica());

    function dibujarMapa(vista, tl, t, dur = 1) {
        const butacas = vista.querySelectorAll('.mapa-butaca');
        tl.fromTo(vista.querySelector('.mapa-pantalla'), { scaleX: 0, transformOrigin: '50% 50%' }, { scaleX: 1, duration: dur * 0.3, ease: 'power2.out' }, t)
          .fromTo(vista.querySelector('.mapa-luz'), { opacity: 0 }, { opacity: 1, duration: dur * 0.4 }, t + dur * 0.1)
          .fromTo(butacas, { scale: 0, opacity: 0, transformOrigin: '50% 50%' },
              { scale: 1, opacity: 1, duration: dur * 0.3, ease: 'back.out(2)', stagger: { amount: dur * 0.7, from: 'start' } }, t + dur * 0.15);
    }

    function ilumina(vista, tl, t) {
        const tuya = vista.querySelector('.mapa-butaca--tuya');
        const chip = vista.querySelector('[data-cuenta]');
        tl.fromTo(tuya, { scale: 1.9, opacity: 0, transformOrigin: '50% 50%' }, { scale: 1, opacity: 1, duration: 0.35, ease: 'back.out(3)' }, t)
          .fromTo(chip, { scale: 0.5, opacity: 0, transformOrigin: '100% 50%' }, { scale: 1, opacity: 1, duration: 0.3, ease: 'back.out(2.5)' }, t + 0.12);
    }

    // Móvil y táctil: cada ilustración se anima una vez al entrar.
    function estatica() {
        const disparadores = [];
        const crear = (vista, construir) => {
            const tl = gsap.timeline({ paused: true });
            construir(tl);
            disparadores.push(ScrollTrigger.create({ trigger: vista, start: 'top 82%', once: true, onEnter: () => tl.play() }));
        };
        crear(v0, (tl) => dibujarMapa(v0, tl, 0, 1.2));
        crear(v1, (tl) => ilumina(v1, tl, 0.1));
        crear(v3, (tl) => tl.fromTo(v3.querySelector('[data-entrada]'), { yPercent: -108 }, { yPercent: 0, duration: 1.6, ease: 'steps(16)' }));
        return () => disparadores.forEach((d) => d.kill());
    }

    // Escritorio: escena fijada.
    function fijada() {
        const pista = seccion.querySelector('[data-compra-pista]');
        const alto = pista.clientHeight;
        const cuentaTexto = v1.querySelector('[data-cuenta-texto]');
        const cuentaBarra = v1.querySelector('[data-cuenta-barra]');
        const entrada = v3.querySelector('[data-entrada]');
        const cabecera = document.querySelector('.site-header');

        let alSeguir = () => {}; // lo asigna más abajo la ilustración de Rive
        // El onUpdate va en la línea de tiempo y no en el ScrollTrigger: con scrub, la línea sigue moviéndose unos instantes
        // después de que el scroll pare, y así el paso marcado y la ilustración de Rive llegan siempre al valor final.
        const tl = gsap.timeline({
            defaults: { ease: 'none' },
            onUpdate: () => { marcar(tl.time()); alSeguir(tl.time()); },
            scrollTrigger: {
                trigger: pista,
                start: () => `top ${cabecera?.offsetHeight ?? 64}px`,
                end: () => `+=${alto * 4.1}`,
                pin: true,
                scrub: 0.6,
                anticipatePin: 1,
                invalidateOnRefresh: true,
            },
        });

        const marcar = (tiempo) => {
            // Cada paso se marca cuando su ilustración ya domina la escena (0,8 · 1,95 · 2,95 en la línea de tiempo)
            const i = tiempo < 0.8 ? 0 : tiempo < 1.95 ? 1 : tiempo < 2.95 ? 2 : 3;
            pasos.forEach((p, n) => (n === i ? p.setAttribute('data-activo', '') : p.removeAttribute('data-activo')));
        };

        // Estado de partida
        const vr = seccion.querySelector('[data-vista-rive]');
        gsap.set([v1, v2, v3, vr].filter(Boolean), { opacity: 0 });
        gsap.set(v2, { xPercent: 8 });

        // 1 · El mapa se dibuja al llegar (con el tiempo, no con el scroll: así la escena nace con el mapa completo)
        const dibujo = gsap.timeline({ paused: true });
        dibujarMapa(v0, dibujo, 0, 1.3);
        const entrada1 = ScrollTrigger.create({ trigger: pista, start: 'top 78%', once: true, onEnter: () => dibujo.play() });

        // 2 · La butaca se ilumina y aparece la cuenta atrás
        tl.to(v0, { opacity: 0, duration: 0.01 }, 0.8)
          .to(v1, { opacity: 1, duration: 0.01 }, 0.8);
        if (vr) tl.to(vr, { opacity: 1, duration: 0.01 }, 0.8);
        ilumina(v1, tl, 0.85);
        const cuenta = { s: 480 };
        tl.to(cuenta, {
            s: 453,
            duration: 1.5,
            onUpdate: () => {
                const s = Math.round(cuenta.s);
                cuentaTexto.textContent = `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
            },
        }, 1.1)
          .to(cuentaBarra, { scaleX: 0.945, duration: 1.5 }, 1.1);

        // 3 · Entra el pago
        tl.to(v1, { opacity: 0, xPercent: -8, duration: 0.3, ease: 'power2.in' }, 1.95)
          .to(v2, { opacity: 1, xPercent: 0, duration: 0.45, ease: 'power3.out' }, 2.15);

        // 4 · La entrada sale como si se imprimiera
        tl.to(v2, { opacity: 0, xPercent: -8, duration: 0.3, ease: 'power2.in' }, 2.95)
          .to(v3, { opacity: 1, duration: 0.2 }, 3.1)
          .fromTo(entrada, { yPercent: -108 }, { yPercent: 0, duration: 0.9, ease: 'steps(18)' }, 3.2);
        tl.to({}, { duration: 0.2 }, 4.1); // pausa final

        // Rive: el tiempo de la línea (0,8 a 4,1) pasa a 0 a 100 y mueve la entrada «progreso».
        let control = null;
        let destruido = false;
        const chip = vr?.querySelector('[data-cuenta-rive]');
        const chipTexto = vr?.querySelector('[data-cuenta-rive-texto]');
        const seguir = (tiempo) => {
            if (!control) return;
            const p = Math.max(0, Math.min(100, ((tiempo - 0.8) / 3.3) * 100));
            control.poner(p);
            // El anillo del archivo se vacía entre los fotogramas 58 y 104 (de 300): la cuenta atrás va de 8:00 a 2:00 a la vez.
            const f = (p / 100) * 300;
            const resto = 480 - 360 * Math.max(0, Math.min(1, (f - 58) / 46));
            chipTexto.textContent = `${Math.floor(resto / 60)}:${String(Math.round(resto) % 60).padStart(2, '0')}`;
            chip.style.opacity = String(Math.max(0, Math.min(1, (f - 40) / 16, (128 - f) / 16)));
        };
        const arranque = ScrollTrigger.create({
            trigger: pista,
            start: 'top bottom+=900',
            once: true,
            onEnter: () => {
                if (!vr || !('IntersectionObserver' in window)) return;
                import('../rive/compra.js')
                    .then((m) => m.montarCompra(vr))
                    .then((c) => {
                        if (destruido) return c.destruir();
                        control = c;
                        seccion.classList.add('compra--rive');
                        seguir(tl.time());
                    })
                    .catch(() => {}); // Sin runtime: se quedan las vistas HTML de siempre.
            },
        });
        alSeguir = seguir;

        marcar(0);
        return () => {
            destruido = true;
            control?.destruir();
            control = null;
            seccion.classList.remove('compra--rive');
            arranque.kill();
            tl.scrollTrigger?.kill();
            tl.kill();
            entrada1.kill();
            dibujo.kill();
            pasos.forEach((p, n) => (n === 0 ? p.setAttribute('data-activo', '') : p.removeAttribute('data-activo')));
            gsap.set([...vistas, vr, ...seccion.querySelectorAll('[data-vista] *')].filter(Boolean), { clearProps: 'transform,opacity' });
        };
    }
}
