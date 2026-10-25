// Ilustración de «Cómo se compra» (rive/compra). Una sola entrada numérica del view model, «progreso» (0 a 100),
// recorre la línea de tiempo del archivo; el ScrollTrigger de movimiento/compra.js la mueve con el scroll.
// El lienzo es decorativo (aria-hidden en la vista): los pasos y la cuenta atrás son HTML.
import { abrir, cargarRive } from './cargar.js';

export async function montarCompra(vista) {
    const m = await cargarRive(vista.dataset.riveSrc);
    const lienzo = vista.querySelector('canvas');
    vista.hidden = false; // el lienzo necesita tamaño para ajustar su superficie
    const r = await abrir(m, { src: vista.dataset.riveSrc, canvas: lienzo, stateMachine: 'Compra', autoplay: true });
    const progreso = r.viewModelInstance?.number('progreso');
    if (!progreso) {
        r.cleanup();
        vista.hidden = true;
        throw new Error('El archivo no tiene la entrada «progreso»');
    }

    // Se dibuja solo mientras cambia el número: al parar el scroll se detiene el bucle.
    let parar = 0;
    let ultimo = 0;
    const pintar = () => {
        clearTimeout(parar);
        r.play();
        parar = setTimeout(() => r.pause(), 160);
    };
    const poner = (valor) => {
        const p = Math.max(0, Math.min(100, valor));
        if (Math.abs(p - ultimo) < 0.01) return;
        ultimo = p;
        progreso.value = p;
        pintar();
    };

    // Al cambiar el tamaño del lienzo (ventana, zoom) la superficie se reajusta y se vuelve a pintar.
    let espera = 0;
    const observador = new ResizeObserver(() => {
        clearTimeout(espera);
        espera = setTimeout(() => {
            r.resizeDrawingSurfaceToCanvas();
            pintar();
        }, 120);
    });
    observador.observe(lienzo);

    pintar();
    return {
        poner,
        destruir() {
            observador.disconnect();
            clearTimeout(parar);
            r.cleanup();
            vista.hidden = true;
        },
    };
}
