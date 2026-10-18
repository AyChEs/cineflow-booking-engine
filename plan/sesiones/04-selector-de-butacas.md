# 04 · Selector de butacas accesible

Modelo: GLM 5.3 (opencode go), razonamiento high
Rama: `mejora/04-butacas`, desde `plan/mejoras`. Nunca `main`.
Depende de: 02 fusionada. Puede ir en paralelo con 03 y 05.
Skills que lee antes de empezar:
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/frontend-ui-engineering/SKILL.md`
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/browser-testing-with-devtools/SKILL.md`
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/incremental-implementation/SKILL.md`

## Contexto

Lee `plan/REGLAS.md`, `plan/DISENO.md` (sección «Estados de la butaca», la más importante de este plan) y «Flujo de compra y bloqueos» de `plan/AUDITORIA.md`.

Hoy, en `resources/views/compra/step2.blade.php`, el mapa es una `<table>` de `<button>` de 22 px con el número, estados por color (`free` gris, `mine` verde, `taken` rojo, `locked` naranja), `onclick="toggleSeat(this)"` y un sondeo periódico a `GET /api/seats/{sesionId}` (`CompraController::seatStatus`). Al pulsar, el JavaScript llama a `POST /api/seat-lock` o `POST /api/seat-unlock` (con CSRF), que crean o borran un `SeatLock` de 8 minutos (`CompraController::lockSeat`/`unlockSeat`, límite de 30 peticiones por minuto). El formulario envía las butacas elegidas en `butaques` (separadas por comas) a `compra.step2.store`. El paso 1 (`compra/step1.blade.php`) elige el número de entradas por tipo con botones `+`/`−` sin nombre accesible.

**El contrato con el servidor no cambia**: mismas rutas, mismos campos, mismos 8 minutos. Esto es la interfaz.

## Tareas

1. **Paso 1** (`step1.blade.php`): cada tipo de entrada es una fila con nombre, precio (`cifras`) y un control de cantidad: botones con `aria-label="Quitar una entrada de adulto"` / `"Añadir una entrada de adulto"` (`x-icon minus/plus`), `input type="number"` con `<label>` (visualmente oculto si hace falta) y `aria-live="polite"` en el total. Botón «Continuar» deshabilitado con explicación («Elige al menos una entrada») mientras el total sea 0. Textos en español.
2. **Mapa** (`step2.blade.php`): saca el JavaScript a `resources/js/seat-map.js` (importado desde `resources/js/app.js` solo si existe `#seat-map`) y el CSS a `resources/css/app.css` (borra el `<style>` de la vista). Estructura:
   ```html
   <div id="seat-map" role="grid" aria-label="Butacas de la sala 3" aria-describedby="seat-legend seat-help" data-required="2" data-sesion="1262">
     <div role="row" aria-label="Fila A">
       <span role="rowheader" class="…">A</span>
       <button role="gridcell" type="button" class="seat seat--libre" data-seat="A1" aria-pressed="false" aria-label="Fila A, butaca 1, libre" tabindex="-1">1</button>
       …
     </div>
   </div>
   ```
   - **Itinerancia del foco** (roving tabindex): solo una butaca tiene `tabindex="0"` (la primera tuya o, si no hay, la primera libre). Flechas mueven el foco (izquierda/derecha en la fila, arriba/abajo a la misma posición de la fila vecina o la más cercana), `Inicio`/`Fin` al principio y final de la fila, `Ctrl+Inicio`/`Ctrl+Fin` a la primera y última butaca. Las butacas vendidas o retenidas **reciben foco** (para poder leer su estado) pero `aria-disabled="true"` y no se pueden pulsar.
   - `Espacio` o `Intro` alternan la butaca (bloquear/soltar), igual que el clic.
   - Estados con las clases `seat--libre`, `seat--tuya` (`aria-pressed="true"`), `seat--retenida`, `seat--vendida`, dibujados exactamente como en la tabla de `plan/DISENO.md` (32 px; 36 px en 390 px; ✓, candado y ✕ como SVG dentro del botón además del número).
   - Encima: la «PANTALLA» (barra `sala-700` curva con el texto `texto-2`), la leyenda `#seat-legend` con las cuatro muestras dibujadas igual y su nombre, y `#seat-help` (texto corto: «Usa las flechas para moverte y Espacio para elegir»).
   - En 390 px el mapa puede desplazarse en horizontal **dentro de su contenedor** (`overflow-x: auto` con `tabindex="0"` y `aria-label`), nunca la página.
3. **Anuncios** (una región `aria-live="polite"` `#seat-status` visualmente oculta):
   - al elegir: «Butaca F7 seleccionada. 1 de 2.»; al soltar: «Butaca F7 liberada. 0 de 2.»;
   - si el servidor rechaza el bloqueo (otra persona la cogió): estado `retenida`, anuncio «La butaca F7 la acaba de coger otra persona» y un aviso visible junto al mapa;
   - si el sondeo cambia butacas que no son tuyas, se actualiza el mapa **sin** anunciar cada cambio;
   - si se llega al número de entradas, intentar elegir otra anuncia «Ya tienes 2 butacas. Suelta una para cambiarla.».
4. **Cuenta atrás del bloqueo** en `compra/_sidebar.blade.php`: «Tus butacas están reservadas durante 7:42» en `cifras`. El controlador ya conoce `expires_at` del `SeatLock`; pásalo a la vista como `data-expires="{{ $expiresAt->toIso8601String() }}"` (si `step2()` no lo envía, añade **solo** esa variable a la vista en `CompraController::step2`, sin cambiar la lógica). El contador visible se actualiza cada segundo, pero el lector de pantalla solo oye avisos en los minutos 5, 2 y 1 y a los 30 s (`aria-live="polite"` en un nodo aparte; el contador visible lleva `aria-hidden="true"` y un `role="timer"` con `aria-label` actualizado cada minuto). Al llegar a 0: el mapa se recarga del servidor, se muestra «El tiempo de reserva ha terminado. Vuelve a elegir tus butacas.» y el foco va a ese mensaje.
5. Botón «Continuar» (carmín) deshabilitado con `aria-disabled` y texto debajo («Elige 2 butacas para continuar») hasta tener todas. El resumen lateral muestra tus butacas en `marquesina` ordenadas («F7, F8»).
6. Respeta `prefers-reduced-motion`: sin escalado al pasar el ratón ni transiciones.
7. Pruebas:
   - `tests/Feature/SeatMapViewTest.php`: el paso 2 devuelve un `role="grid"` con tantas `role="gridcell"` como asientos de la sala; una butaca vendida (crea una `ReservaSeat`) tiene `aria-disabled="true"` y la palabra «vendida» en su `aria-label`; una butaca bloqueada por otro token tiene «retenida».
   - Prueba de navegador con Playwright en `tests/browser/seat-map.spec.mjs` (fuera de PHPUnit, se lanza a mano con `node`): en local, con 2 entradas, recorre el mapa con flechas, elige dos butacas con Espacio, comprueba `aria-pressed` y el texto de `#seat-status`, y que «Continuar» queda activo. Documenta cómo lanzarla en el PR.

## No hacer

- No cambies `lockSeat`, `unlockSeat`, `seatStatus`, `SeatLock`, `PurchaseService` ni la duración de 8 minutos.
- No toques el paso 3 ni Stripe (sesión 05).
- No uses librerías de JavaScript nuevas.

## Criterios de aceptación

- Se puede elegir y soltar butacas solo con teclado; el foco nunca se pierde al actualizar el mapa por el sondeo.
- Ningún estado se distingue solo por color (compruébalo con la captura en escala de grises: `page.emulateMedia({ forcedColors: 'active' })` o filtro CSS `grayscale(1)`).
- axe sin `serious`/`critical` en los pasos 1 y 2 a 390 y 1440 px.
- Pruebas nuevas en verde y las 38 existentes también (incluidas las de carrera).

## Verificación

Ver `plan/REGLAS.md`. Capturas en `plan/capturas/04/`: paso 1, paso 2 con dos butacas tuyas y una retenida (abre una segunda sesión de navegador que bloquee una), paso 2 en escala de grises, y foco visible sobre una butaca.

## Cierre

Commit(s), push, PR hacia `plan/mejoras` con capturas antes/después y la lista de teclas probadas; fusión si está en verde; marca 04 como hecha.
