# 07 · Revisión final

Modelo: GPT-6 Luna (opencode go), razonamiento xhigh. Después, Opus solo revisa el resultado.
Rama: `mejora/07-revision`, desde `plan/mejoras`. Nunca `main`.
Depende de: 00 a 06 fusionadas.
Skills que lee antes de empezar:
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/code-review-and-quality/SKILL.md`
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/verification-before-completion/SKILL.md`

## Contexto

Lee `plan/REGLAS.md`, `plan/DISENO.md`, `plan/PLAN.md`, `plan/AUDITORIA.md` y `plan/specs/05-stripe.md`. Revisa `git diff main...plan/mejoras`.

## Tareas

1. Revisión en cinco ejes (corrección, legibilidad, arquitectura, seguridad, rendimiento) en `plan/REVISION.md`, con archivo, línea, gravedad y arreglo. Atención especial al pago:
   - el webhook verifica la firma antes de leer nada y es idempotente por `event_id` y por `CheckoutPago`;
   - ninguna ruta del navegador confirma una reserva de Stripe;
   - los importes se calculan en el servidor, en céntimos enteros, y no se fían de nada del cliente;
   - los bloqueos se liberan al caducar o fallar y hay red de seguridad si el webhook no llega;
   - no hay secretos en el repositorio (`git log -p main..plan/mejoras | grep -iE "sk_(test|live)_|whsec_"` vacío).
2. Comprueba:
   - Nada contradice el portafolio (lista en `plan/REGLAS.md`). Lo que **amplía** (bloqueo durante el pago, webhook, reembolso) queda resumido en `plan/PARA-EL-PORTAFOLIO.md` en español, con frases cortas, para que la sesión 04 del portafolio actualice el caso.
   - Sesiones y Salas solo tienen cambios visuales: `git diff main...plan/mejoras -- app/Http/Controllers/SesionController.php app/Http/Controllers/SalaController.php app/Models/Sesion.php app/Models/Sala.php` vacío.
   - Sin naranja ni restos del tema claro: `grep -rniE "#d97706|#f7f4f1|cinema-|font-awesome|fonts.bunny" resources`.
   - Sin identificadores de modelos de IA en commits, código ni PR.
3. Arregla los hallazgos menores e importantes. Los bloqueantes que no puedas arreglar sin cambiar el alcance, a `plan/AVISOS.md`.
4. Verificación completa y capturas finales de todas las pantallas de `plan/capturas/antes/` más el mapa de butacas y la entrada en `plan/capturas/final/` (390 y 1440 px).
5. Abre el PR `plan/mejoras → main` **sin fusionarlo**, «Tema de sala, butacas accesibles y pago con Stripe Checkout», con el resumen de las ocho sesiones, enlaces a sus PR, capturas y avisos pendientes.

## No hacer

- No fusiones en `main` ni despliegues.

## Criterios de aceptación

- `plan/REVISION.md` sin bloqueantes abiertos (o con su aviso), todo en verde y el PR a `main` abierto.

## Verificación

Ver `plan/REGLAS.md`, completa.

## Cierre

PR `mejora/07-revision → plan/mejoras` fusionado si está en verde; marca 07 como hecha; PR a `main` abierto. Entrada final en `plan/AVISOS.md`: «Frente CineFlow terminado: PR #N listo para revisar y fusionar».
