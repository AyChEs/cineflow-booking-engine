// Comportamientos que antes vivían en atributos del HTML (onerror, onsubmit). La política de contenido (CSP, middleware
// CabecerasDeSeguridad) no permite manejadores en línea, así que se declaran con atributos data-* y se atienden aquí.
//
//   <img data-respaldo="URL">         si la imagen falla, se prueba esta (una sola vez)
//   <img data-quitar-si-falla>        si la imagen falla, se quita
//   <form data-confirmar="¿Seguro?">  pide confirmación antes de enviar

function alFallarLaImagen(img) {
    if (img.dataset.respaldo && !img.dataset.respaldoProbado) {
        img.dataset.respaldoProbado = '1';
        img.removeAttribute('srcset');
        img.src = img.dataset.respaldo;
    } else if (img.hasAttribute('data-quitar-si-falla')) {
        img.remove();
    }
}

export function iniciarInteracciones() {
    // «error» no sube por el árbol, pero sí se puede capturar en el documento.
    document.addEventListener('error', (e) => {
        if (e.target instanceof HTMLImageElement) alFallarLaImagen(e.target);
    }, true);

    // Imágenes que ya habían fallado antes de que cargara este script.
    document.querySelectorAll('img[data-respaldo], img[data-quitar-si-falla]').forEach((img) => {
        if (img.complete && img.naturalWidth === 0 && img.getAttribute('src')) alFallarLaImagen(img);
    });

    document.addEventListener('submit', (e) => {
        const formulario = e.target;
        if (formulario instanceof HTMLFormElement && formulario.dataset.confirmar && !window.confirm(formulario.dataset.confirmar)) {
            e.preventDefault();
        }
    });
}
