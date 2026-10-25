# 01 · Auditoría (solo informe)

Modelo: GLM 5.3 Flash (opencode go)
Rama: `mejora/01-auditoria`, desde `plan/mejoras`. Nunca `main`.
Depende de: 00 fusionada.
Skills que lee antes de empezar:
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/frontend-ui-engineering/SKILL.md`
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/performance-optimization/SKILL.md`

## Contexto

Lee `plan/REGLAS.md` y `plan/DISENO.md`. Esta sesión **no cambia la aplicación**: deja un informe para las sesiones 02 a 06 y una línea base. Vistas Blade en `resources/views/`, CSS en `resources/css/app.css` (Tailwind 3 + clases propias), JavaScript en línea dentro de las vistas (por ejemplo, el mapa de butacas en `compra/step2.blade.php`) y en `resources/js/app.js`. Rutas en `routes/web.php`.

## Tareas

1. Arranca en local (ver `plan/REGLAS.md`). Si no hay PHP local, usa la demo pública **solo para navegar**, y el paso 2 de compra no se captura (anótalo).
2. Captura y pasa axe a 390 y 1440 px (`plan/herramientas/capturas.mjs`) en: `/`, `/peliculas`, `/peliculas/{id}` (un id con sesiones futuras), `/cines`, `/cines/{id}`, `/login`, `/register`, `/comprar?sesion_id={id}` (paso 1), paso 2 (en local: añade 2 entradas de adulto y pulsa continuar con un script de Playwright propio), `/mis-reservas` y `/dashboard/client` como `cliente@cineflow.test`, `/admin/peliculas` como admin. Guarda todo en `plan/capturas/01/`.
3. Lighthouse móvil y escritorio de `/` y `/peliculas/{id}` (`npx -y lighthouse@12 URL --output=json --output-path=… --chrome-flags="--headless=new"`, con `--preset=desktop` para escritorio).
4. Escribe `plan/AUDITORIA.md` con, en cada hallazgo, archivo y línea:
   - **Resumen**: tabla pantalla × (serias/críticas a 390, a 1440, desplazamiento horizontal) y notas de Lighthouse.
   - **Accesibilidad**: cada violación de axe con su arreglo; además, a mano: mapa de butacas con teclado (hoy botones de 22 px, estados solo por color), botones `+`/`−` sin nombre del paso 1, menú móvil, cuenta atrás del bloqueo (¿existe?, ¿se anuncia?), errores de formulario.
   - **Textos en inglés**: lista de vistas con textos de interfaz en inglés (`grep -rlE ">[A-Z][a-z]+ [a-z]+" resources/views` y revisión a mano), para pasarlos a español en 02 a 06.
   - **Estilos**: todos los bloques `<style>` dentro de vistas (`grep -rn "<style" resources/views`), colores escritos a mano (`grep -rnoE "#[0-9a-fA-F]{3,6}" resources/views resources/css | sort | uniq -c | sort -rn | head -40`) y usos de las clases `cinema-*` de `tailwind.config.js`.
   - **Terceros**: Font Awesome (cdnjs), Figtree (fonts.bunny.net), iconos usados (`grep -rhoE "fa-[a-z-]+" resources/views | sort | uniq -c`), y si algo más se carga de fuera.
   - **Flujo de compra y bloqueos**: cómo funciona hoy de punta a punta (`CompraController::step1` a `confirmacio`, `lockSeat`, `unlockSeat`, `seatStatus`, `releaseLocks`, `SeatLock`, `PurchaseService`, `StripeService`), cada cuánto sondea el mapa, qué pasa al caducar los 8 minutos, y cómo se paga hoy con y sin Stripe. Este apartado lo usan 04 y 05.
   - **Autoría**: para cada vista o controlador que tocará el plan, si pertenece a Sesiones o Salas (trabajo de Ismael y Danna, solo cambios visuales) según `docs/evidencias/fase-16-19.md`.
   - **Propuesta por sesión**: qué resuelve cada una de 02 a 06.
5. No arregles nada. Si hay algo urgente, ponlo en «Urgente» arriba y añádelo al final de las «Tareas» de la sesión que corresponda, con el prefijo «Añadido por 01:».

## No hacer

- Nada fuera de `plan/`.
- En la demo pública no compres ni bloquees butacas.

## Criterios de aceptación

- `plan/AUDITORIA.md` con las ocho secciones y rutas con línea.
- Capturas y `report.json` en `plan/capturas/01/`.
- `git diff --name-only origin/plan/mejoras` solo muestra archivos bajo `plan/`.

## Verificación

```bash
git diff --name-only origin/plan/mejoras | grep -v '^plan/' && echo "ERROR" || echo ok
```

## Cierre

Commit «Añade la auditoría», push, PR hacia `plan/mejoras` con la tabla del resumen, fusión y marca 01 como hecha.
