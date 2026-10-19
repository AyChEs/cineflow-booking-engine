// Cuenta atrás del bloqueo de butacas. El número visible cambia cada segundo, pero
// el lector de pantalla solo oye avisos a los 5, 2 y 1 minutos y a los 30 segundos.

const AVISOS = [300, 120, 60, 30];

function formato(seg) {
    const m = Math.floor(seg / 60);
    const s = seg % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
}

function textoAviso(seg) {
    if (seg >= 60) {
        const m = Math.round(seg / 60);
        return `Tus butacas siguen reservadas ${m === 1 ? '1 minuto' : `${m} minutos`} más.`;
    }
    return 'Quedan 30 segundos de reserva.';
}

export function iniciarCuentaAtras(caja, alTerminar) {
    const valor = document.getElementById('cuenta-atras-valor');
    const timer = document.getElementById('cuenta-atras-timer');
    const aviso = document.getElementById('cuenta-atras-aviso');
    const nota = document.getElementById('cuenta-atras-nota');
    let fin = caja.dataset.expires ? new Date(caja.dataset.expires) : null;
    let avisados = new Set();
    let ultimoMinuto = null;
    let terminado = false;

    function pintar() {
        if (!fin) return;
        const seg = Math.max(0, Math.round((fin.getTime() - Date.now()) / 1000));
        valor.textContent = formato(seg);

        const minuto = Math.ceil(seg / 60);
        if (minuto !== ultimoMinuto) {
            ultimoMinuto = minuto;
            timer.setAttribute('aria-label', `Tus butacas están reservadas durante ${minuto === 1 ? 'menos de 1 minuto' : `${minuto} minutos`}`);
        }

        for (const umbral of AVISOS) {
            if (seg <= umbral && seg > umbral - 5 && !avisados.has(umbral)) {
                avisados.add(umbral);
                aviso.textContent = textoAviso(umbral);
            }
        }

        if (seg === 0 && !terminado) {
            terminado = true;
            fijar(null);
            alTerminar?.();
        }
    }

    function fijar(fecha) {
        const cambia = (fecha?.getTime() ?? null) !== (fin?.getTime() ?? null);
        fin = fecha;
        caja.classList.toggle('hidden', !fin);
        nota?.classList.toggle('hidden', Boolean(fin));
        if (cambia) {
            // Avisos ya superados por la nueva caducidad no se repiten.
            const seg = fin ? Math.round((fin.getTime() - Date.now()) / 1000) : 0;
            avisados = new Set(AVISOS.filter((u) => u > seg));
            terminado = false;
            ultimoMinuto = null;
        }
        pintar();
    }

    window.setInterval(pintar, 1000);
    fijar(fin);

    return { fijar };
}
