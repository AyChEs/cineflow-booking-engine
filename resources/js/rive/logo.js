// Logotipo animado: la butaca se convierte en la entrada y se troquela por la línea de perforaciones
// (rive/logo). Se reproduce una vez al cargar y otra cada vez que se pasa el ratón o el foco por encima.
import { abrir, listo } from './cargar.js';

export async function montarLogo(el, m) {
    const r = await abrir(m, {
        src: el.dataset.riveSrc,
        canvas: el.querySelector('canvas'),
        stateMachine: 'Troquelado',
        autoplay: true,
    });
    listo(el);

    // La animación dura 2 s. Pasado ese tiempo se detiene el dibujo (un lienzo que se repinta 60 veces por
    // segundo sin cambiar gasta batería) y se reanuda en la siguiente pasada del ratón.
    let parar = 0;
    const reproducir = (disparar) => {
        clearTimeout(parar);
        r.play();
        if (disparar) r.viewModelInstance?.trigger('repetir')?.trigger();
        parar = setTimeout(() => r.pause(), 2300);
    };
    reproducir(false);

    const zona = el.closest('a, .marca-logo') ?? el;
    zona.addEventListener('pointerenter', (e) => e.pointerType !== 'touch' && reproducir(true));
    zona.addEventListener('focusin', () => reproducir(true));
}
