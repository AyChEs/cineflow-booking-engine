// Runtime de Rive (@rive-app/canvas-lite, MIT). Se descarga solo cuando una pieza lo pide y con su
// WebAssembly alojado en public/rive/: no depende de ningún CDN. Todas las piezas comparten una sola carga.

let promesa = null;

export function cargarRive(origen) {
    promesa ??= import('@rive-app/canvas-lite').then((m) => {
        m.RuntimeLoader.setWasmUrl(new URL('rive.wasm', new URL(origen, location.href)).href);
        m.RuntimeLoader.setWasmFallbackUrl(null);
        return m;
    });
    return promesa;
}

// Abre un .riv en un lienzo y resuelve cuando el artboard está listo y ajustado a la densidad de píxeles.
export function abrir(m, opciones) {
    return new Promise((resolver, rechazar) => {
        const r = new m.Rive({
            shouldDisableRiveListeners: true,
            autoBind: true,
            layout: new m.Layout({ fit: m.Fit.Contain, alignment: m.Alignment.Center }),
            ...opciones,
            onLoad: () => {
                r.resizeDrawingSurfaceToCanvas();
                resolver(r);
            },
            onLoadError: () => rechazar(new Error(`No se pudo abrir ${opciones.src}`)),
        });
    });
}

// Dos fotogramas después, el lienzo ya ha pintado: el SVG de reserva se retira sin parpadeo.
export function listo(el) {
    requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('rive-listo')));
}
