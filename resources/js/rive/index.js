// Piezas de Rive (sesión 11). Un chunk aparte que app.js importa después de la carga y solo donde hay
// algún [data-rive]. Si el runtime o un archivo no cargan, cada pieza se queda con su SVG o su HTML.
import { cargarRive } from './cargar.js';

const piezas = {
    logo: () => import('./logo.js').then((p) => p.montarLogo),
    anillo: () => import('./anillo.js').then((p) => p.montarAnillo),
};

// Cada pieza se monta cuando entra en pantalla (una pieza oculta, como la cuenta atrás antes de elegir butaca,
// no descarga nada). Todas comparten un solo runtime.
export function iniciarRive() {
    // El logotipo reacciona al ratón y se anima al cargar: en táctil (sin hover) no compensa bajar ~400 KB de runtime
    // por un símbolo de 31 px, así que allí se queda el SVG. El anillo de la compra sí se anima en todos los dispositivos.
    const conRaton = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const elementos = [...document.querySelectorAll('[data-rive]')].filter((el) => piezas[el.dataset.rive] && (conRaton || el.dataset.rive !== 'logo'));
    if (!elementos.length || !('IntersectionObserver' in window)) return;
    const observador = new IntersectionObserver((entradas) => {
        for (const entrada of entradas) {
            if (!entrada.isIntersecting) continue;
            const el = entrada.target;
            observador.unobserve(el);
            Promise.all([cargarRive(el.dataset.riveSrc), piezas[el.dataset.rive]()])
                .then(([m, montar]) => montar(el, m))
                .catch(() => {}); // Sin runtime o sin archivo se queda el SVG de reserva.
        }
    }, { rootMargin: '200px' });
    elementos.forEach((el) => observador.observe(el));
}
