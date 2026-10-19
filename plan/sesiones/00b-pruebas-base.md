# 00b · Pruebas de la línea base en verde

Rama: `mejora/00b-pruebas-base`, desde `plan/mejoras`. Nunca `main`.
Depende de: 00 fusionada. Va antes que 01 (o a la vez): sin ella ningún PR puede salir en verde.
Skills: `debugging-and-error-recovery`, `verification-before-completion`.

## Contexto

La sesión 00 dejó anotado que 3 pruebas fallaban antes de tocar nada. Comprobado en `main` (e8a255f): fallan esas 3 y una cuarta de forma intermitente. Esta sesión arregla la causa raíz de cada una **sin cambiar, saltar ni rebajar ninguna prueba**.

| Prueba | Causa raíz | Arreglo |
|---|---|---|
| `AuthenticationTest > users can authenticate…` (intermitente, 1 de cada 3) | `UserFactory` elegía el rol al azar; con `cliente`, el login redirigía a `/dashboard/client`. | La factoría usa `cliente` fijo (estados `admin()` y `taquilla()` para lo demás). El login siempre va a `/dashboard`, y esa ruta manda a los clientes a su panel: mismo destino final para cada rol. |
| `PasswordResetTest > reset password link is generated in demo mode` | La prueba (y su comentario) describen un modo demo sin correo que el controlador no tenía. | `PasswordResetLinkController` genera el token y deja `reset_link` en la sesión cuando `auth.demo_reset_link` está activo: por defecto solo con `MAIL_MAILER` `log`/`array` y **fuera de producción** (en producción permitiría cambiar la contraseña de cualquier cuenta). Forzable con `AUTH_DEMO_RESET_LINK`. |
| `PurchaseFlowTest > user can complete purchase flow` y `> race condition prevents double booking` | El pago simulado solo aceptaba `metode=targeta`; las pruebas y `docs/04-logica-negocio.md` usan Bizum. La reserva no se creaba y la prueba de carrera no llegaba a probar nada. | `step3Store` acepta `bizum` con `telefon_bizum` (móvil español: 9 cifras, empieza por 6 o 7). La opción visible en el paso 3 llega con la sesión 05, que rehace esa vista. |

## Criterios de aceptación

- `php artisan test --display-skipped`: todas en verde, ninguna saltada, estable en varias ejecuciones seguidas.
- Las pruebas existentes no cambian (`git diff --stat origin/plan/mejoras -- tests` solo muestra archivos nuevos).
- Pruebas nuevas de regresión en `tests/Feature/BaseFixesTest.php`.
- CI del PR en verde.
