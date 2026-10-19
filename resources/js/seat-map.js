// Mapa de butacas accesible (sesión 04): rejilla con foco itinerante, estados con
// forma y texto, anuncios para lector de pantalla y sondeo sin perder el foco.
// El contrato con el servidor no cambia: /api/seat-lock, /api/seat-unlock y /api/seats/{id}.

const TEXTO_ESTADO = {
    libre: 'libre',
    tuya: 'seleccionada por ti',
    retenida: 'retenida por otra persona',
    vendida: 'vendida',
};
const MINUTOS_BLOQUEO = 8;
const SONDEO_MS = 10000;

export function iniciarMapa(raiz, cuentaAtras) {
    const requeridas = parseInt(raiz.dataset.required, 10);
    const sesionId = parseInt(raiz.dataset.sesion, 10);
    const csrf = document.querySelector('#step2form input[name="_token"]')?.value ?? '';
    const estadoVivo = document.getElementById('seat-status');
    const aviso = document.getElementById('seat-aviso');
    const finReserva = document.getElementById('fin-reserva');
    const entrada = document.getElementById('butaquesInput');
    const contador = document.getElementById('selCount');
    const continuar = document.getElementById('btnSeguent');
    const ayuda = document.getElementById('ayuda-continuar');
    const resumen = document.getElementById('resumen-butacas');
    const form = document.getElementById('step2form');

    const filas = Array.from(raiz.querySelectorAll('[role="row"]')).map((f) => Array.from(f.querySelectorAll('button.seat')));
    const butacas = filas.flat();
    const posicion = new Map();
    filas.forEach((fila, r) => fila.forEach((b, c) => posicion.set(b, [r, c])));
    const porId = new Map(butacas.map((b) => [b.dataset.seat, b]));

    // Caducidad de cada butaca propia: la del servidor para las ya bloqueadas, 8 minutos para las nuevas.
    const caducidad = new Map();
    const expiraServidor = raiz.dataset.expira ? new Date(raiz.dataset.expira) : null;
    butacas.filter((b) => b.dataset.estado === 'tuya').forEach((b) => caducidad.set(b.dataset.seat, expiraServidor ?? enMinutos(MINUTOS_BLOQUEO)));
    const pendientes = new Set();

    function enMinutos(m) {
        return new Date(Date.now() + m * 60000);
    }

    function anunciar(texto) {
        // Vaciar y volver a escribir para que se anuncie aunque el texto se repita.
        estadoVivo.textContent = '';
        window.setTimeout(() => { estadoVivo.textContent = texto; }, 30);
    }

    function elegidas() {
        return butacas.filter((b) => b.dataset.estado === 'tuya').map((b) => b.dataset.seat);
    }

    function ordenar(ids) {
        return ids.slice().sort((a, b) => {
            const fa = a.replace(/\d+/g, ''), fb = b.replace(/\d+/g, '');
            return fa === fb ? parseInt(a.replace(/\D+/g, ''), 10) - parseInt(b.replace(/\D+/g, ''), 10) : fa.localeCompare(fb);
        });
    }

    function ponerEstado(b, estado) {
        b.dataset.estado = estado;
        b.className = `seat seat--${estado}`;
        b.setAttribute('aria-pressed', estado === 'tuya' ? 'true' : 'false');
        if (estado === 'vendida' || estado === 'retenida') b.setAttribute('aria-disabled', 'true');
        else b.removeAttribute('aria-disabled');
        b.setAttribute('aria-label', `Fila ${b.dataset.fila}, butaca ${b.dataset.num}, ${TEXTO_ESTADO[estado]}`);
        if (estado !== 'tuya') caducidad.delete(b.dataset.seat);
    }

    function actualizarResumen() {
        const ids = ordenar(elegidas());
        entrada.value = ids.join(',');
        contador.textContent = ids.length;
        if (resumen) resumen.textContent = ids.length ? ids.join(', ') : 'Sin elegir';
        const completa = ids.length === requeridas;
        continuar.setAttribute('aria-disabled', completa ? 'false' : 'true');
        ayuda.hidden = completa;

        const fechas = Array.from(caducidad.values());
        cuentaAtras?.fijar(fechas.length ? new Date(Math.min(...fechas)) : null);
    }

    // Foco itinerante: solo una butaca tiene tabindex="0".
    function activar(b, enfocar = true) {
        butacas.forEach((x) => { x.tabIndex = -1; });
        b.tabIndex = 0;
        if (enfocar) b.focus();
    }

    function inicial() {
        return butacas.find((b) => b.dataset.estado === 'tuya')
            ?? butacas.find((b) => b.dataset.estado === 'libre')
            ?? butacas[0];
    }

    async function pedir(url, butaca) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ sesion_id: sesionId, butaca }),
        });
        if (res.status === 429) return { ok: false, reason: 'limite' };
        if (!res.ok) return { ok: false, reason: 'error' };
        return res.json();
    }

    function mostrarAviso(texto) {
        aviso.textContent = texto;
        aviso.hidden = !texto;
    }

    async function alternar(b) {
        const id = b.dataset.seat;
        if (pendientes.has(id)) return;
        const estado = b.dataset.estado;

        if (estado === 'vendida' || estado === 'retenida') {
            anunciar(`La butaca ${id} está ${TEXTO_ESTADO[estado]}. Elige otra.`);
            return;
        }

        finReserva.hidden = true;

        if (estado === 'tuya') {
            pendientes.add(id);
            ponerEstado(b, 'libre');
            actualizarResumen();
            anunciar(`Butaca ${id} liberada. ${elegidas().length} de ${requeridas}.`);
            try { await pedir(raiz.dataset.unlockUrl, id); } finally { pendientes.delete(id); }
            return;
        }

        if (elegidas().length >= requeridas) {
            anunciar(`Ya tienes ${requeridas} ${requeridas === 1 ? 'butaca' : 'butacas'}. Suelta una para cambiarla.`);
            return;
        }

        pendientes.add(id);
        b.classList.add('is-pendiente');
        let datos;
        try {
            datos = await pedir(raiz.dataset.lockUrl, id);
        } catch (_) {
            datos = { ok: false, reason: 'error' };
        } finally {
            pendientes.delete(id);
            b.classList.remove('is-pendiente');
        }

        if (datos.ok) {
            ponerEstado(b, 'tuya');
            caducidad.set(id, enMinutos(MINUTOS_BLOQUEO));
            mostrarAviso('');
            actualizarResumen();
            anunciar(`Butaca ${id} seleccionada. ${elegidas().length} de ${requeridas}.`);
        } else if (datos.reason === 'taken' || datos.reason === 'locked') {
            ponerEstado(b, datos.reason === 'taken' ? 'vendida' : 'retenida');
            actualizarResumen();
            const texto = `La butaca ${id} la acaba de coger otra persona.`;
            mostrarAviso(texto);
            anunciar(texto);
        } else {
            const texto = datos.reason === 'limite'
                ? 'Vas muy rápido. Espera unos segundos y vuelve a intentarlo.'
                : 'No hemos podido guardar la butaca. Vuelve a intentarlo.';
            mostrarAviso(texto);
            anunciar(texto);
        }
    }

    // Sondeo: actualiza el mapa sin anunciar cada cambio y sin mover el foco.
    async function sondear() {
        let datos;
        try {
            const res = await fetch(raiz.dataset.statusUrl, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            datos = await res.json();
        } catch (_) {
            return;
        }
        const vendidas = new Set(datos.taken), retenidas = new Set(datos.locked), mias = new Set(datos.mine);
        butacas.forEach((b) => {
            const id = b.dataset.seat;
            if (pendientes.has(id)) return;
            const nuevo = vendidas.has(id) ? 'vendida' : retenidas.has(id) ? 'retenida' : mias.has(id) ? 'tuya' : 'libre';
            if (nuevo !== b.dataset.estado) {
                ponerEstado(b, nuevo);
                if (nuevo === 'tuya' && !caducidad.has(id)) caducidad.set(id, enMinutos(MINUTOS_BLOQUEO));
            }
        });
        actualizarResumen();
    }

    // Teclado: flechas, Inicio/Fin y Ctrl+Inicio/Fin. Espacio e Intro llegan como clic del botón.
    raiz.addEventListener('keydown', (e) => {
        const b = e.target.closest('button.seat');
        if (!b) return;
        const [r, c] = posicion.get(b);
        let destino = null;
        switch (e.key) {
            case 'ArrowRight': destino = filas[r][c + 1]; break;
            case 'ArrowLeft': destino = filas[r][c - 1]; break;
            case 'ArrowDown': destino = filas[r + 1]?.[Math.min(c, filas[r + 1].length - 1)]; break;
            case 'ArrowUp': destino = filas[r - 1]?.[Math.min(c, filas[r - 1].length - 1)]; break;
            case 'Home': destino = e.ctrlKey ? butacas[0] : filas[r][0]; break;
            case 'End': destino = e.ctrlKey ? butacas[butacas.length - 1] : filas[r][filas[r].length - 1]; break;
            default: return;
        }
        e.preventDefault();
        if (destino) activar(destino);
    });

    raiz.addEventListener('click', (e) => {
        const b = e.target.closest('button.seat');
        if (!b) return;
        activar(b);
        alternar(b);
    });

    form.addEventListener('submit', (e) => {
        if (continuar.getAttribute('aria-disabled') === 'true') {
            e.preventDefault();
            const faltan = requeridas - elegidas().length;
            anunciar(faltan > 0 ? `Te ${faltan === 1 ? 'falta 1 butaca' : `faltan ${faltan} butacas`} por elegir.` : 'Revisa tus butacas.');
            activar(butacas.find((b) => b.tabIndex === 0) ?? inicial());
        }
    });

    activar(inicial(), false);
    actualizarResumen();
    window.setInterval(sondear, SONDEO_MS);

    return {
        // Al caducar el bloqueo: el mapa se recarga del servidor y el foco va al mensaje.
        async caducado() {
            caducidad.clear();
            await sondear();
            butacas.filter((b) => b.dataset.estado === 'tuya').forEach((b) => ponerEstado(b, 'libre'));
            actualizarResumen();
            finReserva.hidden = false;
            finReserva.focus();
        },
    };
}
