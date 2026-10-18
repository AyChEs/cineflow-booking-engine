# Reglas comunes de todas las sesiones (CineFlow)

Léelo entero antes de empezar cualquier sesión. Si una skill choca con esto, manda esto.

## Quién y para qué

- CineFlow es el proyecto final de ciclo de **un equipo**: Ayman Charoui, Ismael Achamrouk y Danna Guevara. Ayman lo retomó después por su cuenta (despliegue, CI y Stripe). Es un caso del portafolio de Ayman: un reclutador abrirá la demo (`https://cineflow-ufe0.onrender.com/`) después de leer el caso.
- Reparto original (`docs/evidencias/fase-16-19.md`): Ayman hizo Películas, Ismael Sesiones y Danna Salas; Categorías e integración, todo el equipo.
- Lo que el portafolio afirma y **no puede dejar de ser cierto**: Laravel 12 y PHP 8.4; bloqueo temporal de la butaca de 8 minutos (`SeatLock`) al elegirla; confirmación en una transacción con `lockForUpdate` y hasta 3 intentos; precio por tipo de entrada (adulto, reducida, familiar, mayor de 65); 38 tests con PHPUnit, incluidos los de carrera; entradas con QR firmado; Stripe en modo de pruebas. Si un cambio contradice algo de esto, para y deja un aviso.

## Reglas que no se negocian

- **Trabajo del equipo:** en código de Sesiones (`SesionController`, `Sesion`, vistas `sesiones/`) y Salas (`SalaController`, `Sala`, vistas `salas/`) solo se permiten cambios visuales (clases, marcado, textos). Cambiar su lógica, rutas o modelos es un aviso.
- Prohibido el naranja (matiz entre 15° y 40°), también como «ámbar» de estado. Hoy `step2.blade.php` usa `#d97706` para «bloqueada por otros»: se elimina.
- No usar nada de la marca del portafolio: azul marino `#14213D`, azul `#2563EB`/`#3B82F6`, Plus Jakarta Sans, JetBrains Mono, el símbolo λ.
- Sin degradados morados. Sin servicios de pago. No se cambia el hosting (Render).
- WCAG 2.2 AA. Toda animación respeta `prefers-reduced-motion`.
- Textos visibles: frases cortas, sin exclamaciones, sin rayas largas (—), sin «robusto», «soluciones», «apasionado».
- Ningún identificador de modelo de IA en commits, PR, código ni comentarios. Ningún «Co-Authored-By» de una IA.
- Nunca se salta, desactiva ni rebaja una prueba para que pase. Si una prueba ya fallaba antes de tocar nada, se anota en el PR y no se arregla en esa sesión.
- La dirección visual está en `plan/DISENO.md`. No inventes colores, tamaños ni radios fuera de ella.
- **Idioma de la interfaz: español.** La cartelera y las sinopsis ya están en español y hoy la interfaz está en inglés. Cada sesión pasa a español los textos de las vistas que toca. El código (nombres de variables, rutas) no se traduce.

## Ramas y PR

- Rama del plan: `plan/mejoras`. Cada sesión crea su rama desde ella: `git fetch origin && git checkout plan/mejoras && git pull && git checkout -b mejora/NN-nombre`.
- Nunca trabajes en `main` ni abras PR hacia `main`.
- Commits pequeños, en español, en imperativo.
- Al terminar: `git push -u origin mejora/NN-nombre` y `gh pr create --base plan/mejoras --title "NN · Título" --body-file /tmp/pr-NN.md`.
- El cuerpo del PR lleva: resumen, decisiones tomadas por tu cuenta y su porqué, capturas antes/después (`plan/capturas/NN/`), salida resumida de la verificación y lo que quedó fuera.
- Si la verificación está en verde (local y `gh pr checks --watch`), fusiona tú: `gh pr merge --squash --delete-branch`. Después marca la sesión como hecha en `plan/PLAN.md` (en `plan/mejoras`, commit «Marca la sesión NN como hecha»).

## Entorno de esta máquina

- Hay Node 22 y `gh` con sesión de `AyChEs`. **No hay PHP, Composer ni Docker en el sistema** (comprobado el 2026-10-18). La sesión 00 deja un PHP 8.4 y Composer en `~/.local/php/` y añade aquí cómo usarlos. Si no lo consigue, la verificación de PHP se hace solo en CI.
- Arranque local (tras la sesión 00): `cp -n .env.example .env && php artisan key:generate && touch database/database.sqlite && php artisan migrate:fresh --seed && npm ci && npm run build && php artisan serve --port=8000`.
- Cuentas del seeder: `admin@cineflow.test` / `admin1234`, `taquilla@cineflow.test` / `taquilla1234`, `cliente@cineflow.test` / `cliente1234`.
- Contra la demo pública (`cineflow-ufe0.onrender.com`) solo se navega. No se compra, no se bloquean butacas a propósito y no se crean cuentas.

## Verificación (obligatoria antes de cada PR)

1. `php artisan test`: 38 pruebas o más, ninguna fallida ni saltada (`--display-skipped` no muestra ninguna). Si no hay PHP local, el job `tests` del CI del PR.
2. `npm run build` sin errores.
3. Capturas y axe a 390 y 1440 px de cada pantalla tocada, contra `http://localhost:8000`:
   ```bash
   cd /tmp && mkdir -p capt && cd capt && [ -d node_modules/playwright ] || (npm init -y >/dev/null && npm i playwright@1.56 @axe-core/playwright >/dev/null && npx playwright install chromium)
   node /RUTA/AL/REPO/plan/herramientas/capturas.mjs NN http://localhost:8000 '[{"id":"cartelera","url":"/peliculas"}]' '{"url":"/login","user":"#email","pass":"#password","submit":"button[type=submit]"}'
   ```
   Copia `/tmp/capt/out/NN/` a `plan/capturas/NN/`. El `report.json` no puede tener violaciones `serious` ni `critical` en las pantallas tocadas, ni `hscroll: true`.

## Avisos a Ayman

Para y escribe en `plan/AVISOS.md` (solo esa tarea; sigue con el resto) cuando:
- falta una credencial, clave o cuenta (por ejemplo, claves de Stripe en modo de prueba);
- falta acceso de escritura al repositorio;
- la acción es irreversible o hacia fuera: fusionar en `main`, desplegar, borrar datos, publicar, escribir a alguien, cambiar el hosting, contratar algo;
- un cambio choca con estas reglas o con lo que afirma el portafolio;
- hay que reescribir más allá de lo visual una parte hecha por Ismael o Danna;
- la verificación falla dos veces seguidas por lo mismo y la causa no está en el código de la sesión.

Formato (añade al final, no borres los anteriores):

```markdown
## AAAA-MM-DD · Sesión NN · Título corto
- Qué pasa:
- Opciones: 1) … 2) …
- Recomiendo: … porque …
- Queda bloqueado: …
```

Nada más se le pregunta. Lo razonable lo decides tú y lo anotas en el PR.

## Skills

Están en el portafolio, en esta misma máquina: `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/<nombre>/SKILL.md`. Lee solo las que indique tu sesión.
