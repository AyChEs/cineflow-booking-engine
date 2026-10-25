// Anillo de la cuenta atrás de la compra (rive/anillo). Es decorativo: el número y el aviso al lector de
// pantalla siguen siendo HTML. Lee los segundos restantes del evento «cuenta:segundos» que emite cuenta-atras.js.
import { abrir, listo } from './cargar.js';

export async function montarAnillo(el, m) {
    const caja = el.closest('#cuenta-atras');
    const r = await abrir(m, { src: el.dataset.riveSrc, canvas: el.querySelector('canvas'), stateMachine: 'Anillo', autoplay: true });
    const resto = r.viewModelInstance?.number('resto');
    if (!resto) return;
    // El número cambia una vez por segundo: se despierta el dibujo, se pinta el cambio y se vuelve a detener.
    let parar = 0;
    const poner = (seg) => {
        clearTimeout(parar);
        r.play();
        resto.value = seg;
        parar = setTimeout(() => r.pause(), 150);
    };
    caja?.addEventListener('cuenta:segundos', (e) => poner(e.detail.segundos));
    // Valor actual: el que dejó --resto la última vez que corrió la cuenta atrás.
    const actual = parseFloat(caja?.style.getPropertyValue('--resto'));
    if (!Number.isNaN(actual)) poner(Math.round(actual * 480));
    listo(el);
}
