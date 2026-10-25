import './bootstrap';

import Alpine from 'alpinejs';
import { enfocarPrimerError, iniciarApariciones, iniciarMenu } from './menu';

window.Alpine = Alpine;

Alpine.start();

iniciarMenu();
iniciarApariciones();
enfocarPrimerError();

// Movimiento de las páginas públicas (sesión 09): un chunk aparte, solo donde el layout declara data-mov y
// solo si no se pide reducir el movimiento (en ese caso no se descarga ni Lenis ni GSAP).
const modoMovimiento = document.body.dataset.mov;
if (modoMovimiento && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const cargarMovimiento = () => import('./movimiento/index.js')
        .then((m) => m.iniciar(modoMovimiento))
        .catch(() => document.documentElement.classList.remove('mov-ok', 'mov-escena'));
    // Después de que la página haya pintado (así no compite con la imagen principal ni con la fuente).
    const cuandoCargue = () => ('requestIdleCallback' in window ? requestIdleCallback(cargarMovimiento, { timeout: 1200 }) : setTimeout(cargarMovimiento, 200));
    if (document.readyState === 'complete') cuandoCargue();
    else window.addEventListener('load', cuandoCargue, { once: true });
}

// Piezas de Rive (sesión 11): logotipo animado, ilustración de «Cómo se compra» y anillo de la cuenta atrás.
// Un chunk aparte, después de la carga y solo con movimiento permitido. Sin él cada pieza se queda con su SVG o su HTML.
if (document.querySelector('[data-rive]') && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const cargarRive = () => import('./rive/index.js').then((m) => m.iniciarRive()).catch(() => {});
    const alCargar = () => ('requestIdleCallback' in window ? requestIdleCallback(cargarRive, { timeout: 2500 }) : setTimeout(cargarRive, 400));
    if (document.readyState === 'complete') alCargar();
    else window.addEventListener('load', alCargar, { once: true });
}

// Compra: mapa de butacas (paso 2) y cuenta atrás del bloqueo (pasos 2 y 3), solo donde existen.
const mapa = document.getElementById('seat-map');
const caja = document.getElementById('cuenta-atras');
if (mapa || caja) {
    Promise.all([
        caja ? import('./cuenta-atras') : null,
        mapa ? import('./seat-map') : null,
    ]).then(([moduloCuenta, moduloMapa]) => {
        let controlMapa = null;
        const cuenta = moduloCuenta?.iniciarCuentaAtras(caja, () => {
            if (controlMapa) {
                controlMapa.caducado();
            } else {
                document.getElementById('fin-reserva')?.removeAttribute('hidden');
                document.getElementById('fin-reserva')?.focus();
            }
        });
        if (moduloMapa) controlMapa = moduloMapa.iniciarMapa(mapa, cuenta);
    });
}

// Escáner de entradas de la taquilla: carga la librería solo en esa página.
if (document.getElementById('reader')) {
    import('./escaner');
}
