import './bootstrap';

import Alpine from 'alpinejs';
import { enfocarPrimerError, iniciarApariciones, iniciarMenu } from './menu';

window.Alpine = Alpine;

Alpine.start();

iniciarMenu();
iniciarApariciones();
enfocarPrimerError();

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
