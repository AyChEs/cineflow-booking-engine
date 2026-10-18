# 06 · Accesibilidad global, rendimiento y textos

Modelo: GLM 5.3 (opencode go), razonamiento high
Rama: `mejora/06-accesibilidad`, desde `plan/mejoras`. Nunca `main`.
Depende de: 03, 04 y 05 fusionadas.
Skills que lee antes de empezar:
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/frontend-ui-engineering/SKILL.md`
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/performance-optimization/SKILL.md`

## Contexto

Lee `plan/REGLAS.md`, `plan/DISENO.md` y `plan/AUDITORIA.md`. Las sesiones 02 a 05 han rehecho la parte pública y la compra. Quedan: las vistas que nadie ha tocado (confirmación, mis reservas, panel de cliente, entrada con QR, perfil, panel de administración y taquilla), los textos en inglés que queden, y el rendimiento.

## Tareas

1. Repasa los hallazgos de «Accesibilidad» y «Textos en inglés» de la auditoría; marca en el PR cuáles resolvieron 02 a 05 y resuelve aquí el resto.
2. Vistas pendientes con el tema y los componentes de 02: `compra/confirmacio.blade.php` (la entrada: película, cine, sala, día, hora, butacas en `marquesina`, QR con `alt="Código QR de tu entrada"` y el código en texto debajo), `usuarios/misReservas.blade.php`, `cliente/dashboard.blade.php`, `auth/dashboard.blade.php`, `reservas/*`, `taquilla/scanner.blade.php`, `emails/entrada.blade.php` (el correo puede seguir claro: los clientes de correo no respetan el modo oscuro; solo textos en español y sin naranja).
3. Panel de administración (`peliculas/admin-index`, `peliculas/create|edit|external-*`, `cines/admin-index|create|edit`, `salas/*`, `sesiones/*`, `usuarios/*`): **solo** que se lea bien en oscuro (sin `bg-white`, `text-gray-*` claros sobre oscuro), etiquetas en todos los campos, tablas con `<th scope>` y textos en español. Recuerda: `salas/*` y `sesiones/*` son de Danna e Ismael; solo cambios visuales.
4. Global: un `<h1>` por página; títulos de documento con `@section('title', '… · CineFlow')` en todas las vistas; enlaces que se distinguen del texto por algo más que el color (subrayado); objetivos táctiles ≥ 24 px y 44 px en controles principales en 390 px; errores de formulario con `aria-invalid`/`aria-describedby` y foco en el primero al fallar la validación.
5. Rendimiento:
   - Pósteres: `width`/`height`, `loading="lazy"` salvo el de la portada (`fetchpriority="high"`), `decoding="async"`. Si salen de TMDB, usa el tamaño adecuado (`w342` en tarjetas, `w500` en la ficha) cambiando solo la URL en la vista.
   - Sin peticiones a terceros además de TMDB y Stripe (`grep -rnE "https?://" resources/views | grep -vE "tmdb|stripe|github"` revisado).
   - Lighthouse móvil ≥ 90 en rendimiento y 100 en accesibilidad en `/`, `/peliculas` y `/peliculas/{id}`. Si rendimiento no llega por Render (arranque en frío), mide en local y anótalo.
6. Prueba automática de accesibilidad: `tests/browser/a11y.spec.mjs` (Playwright + `@axe-core/playwright` como devDependencies de npm) que, con la app en local, recorre las 14 pantallas de «Terminado significa» de `plan/PLAN.md` a 390 y 1440 px y falla con `serious`/`critical`. Añade un job `a11y` al CI que: instala PHP como el job `tests`, prepara la base con `migrate:fresh --seed`, arranca `php artisan serve &`, espera a `curl -sf localhost:8000`, `npx playwright install --with-deps chromium` y ejecuta la prueba.
7. README: capturas nuevas (sustituye la referencia si hay, o añade una tabla con portada, mapa de butacas y entrada) y la sección de accesibilidad (rejilla con teclado, estados con forma y texto, cuenta atrás anunciada).

## No hacer

- No cambies lógica de controladores, modelos ni rutas (salvo pasar datos nuevos a una vista, sin cambiar comportamiento).
- No cambies la compra, el mapa ni Stripe más allá de arreglos de accesibilidad que señale axe.

## Criterios de aceptación

- `grep -rnE ">\s*(Buy|Select|Next|Back|Sold|Available|Seats?|Login|Log in|Register|Cancel|Confirm)\b" resources/views` vacío (salvo nombres propios).
- La prueba `a11y` en verde en local y en CI.
- Lighthouse según la tarea 5.

## Verificación

Ver `plan/REGLAS.md` más la prueba `a11y`. Capturas en `plan/capturas/06/`.

## Cierre

Commit(s), push, PR hacia `plan/mejoras` con capturas y notas de Lighthouse antes/después; fusión si está en verde; marca 06 como hecha.
