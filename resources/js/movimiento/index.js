// Movimiento de las páginas públicas (sesión 09). Se carga como un chunk aparte y solo donde el layout
// declara `data-mov` en el body; la compra, el acceso y el panel no lo descargan.
// Solo se anima transform y opacity. Con prefers-reduced-motion: reduce ni siquiera se importa (app.js).
import Lenis from 'lenis';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';

import { iniciarInclinacion, iniciarMagneticos, iniciarPieLogo } from './micro.js';
import { iniciarHaz } from './haz.js';
import { iniciarPortada } from './portada.js';
import { iniciarCompra } from './compra.js';
import { iniciarNombre } from './nombre.js';

gsap.registerPlugin(ScrollTrigger, SplitText);

export function iniciar(modo) {
    window.__mov = true;
    const raiz = document.documentElement;

    // Scroll suave solo con ratón o trackpad; en táctil el scroll nativo es mejor.
    if (window.matchMedia('(pointer: fine)').matches) {
        const lenis = new Lenis({ lerp: 0.11, wheelMultiplier: 0.95 });
        lenis.on('scroll', ScrollTrigger.update);
        gsap.ticker.add((t) => lenis.raf(t * 1000));
        gsap.ticker.lagSmoothing(0);
        // Con el menú móvil abierto el body no debe desplazarse.
        new MutationObserver(() => (document.body.style.overflow === 'hidden' ? lenis.stop() : lenis.start()))
            .observe(document.body, { attributes: true, attributeFilter: ['style'] });
    }

    iniciarInclinacion();
    iniciarMagneticos(gsap);
    iniciarPieLogo(gsap, ScrollTrigger);
    iniciarHaz(gsap, ScrollTrigger);

    if (modo === 'portada') {
        iniciarPortada({ gsap, ScrollTrigger, SplitText });
        iniciarCompra({ gsap, ScrollTrigger });
        iniciarNombre({ gsap, ScrollTrigger });
    }

    raiz.classList.add('mov-listo');
    if (document.fonts?.ready) document.fonts.ready.then(() => ScrollTrigger.refresh());
    window.addEventListener('load', () => ScrollTrigger.refresh());
}
