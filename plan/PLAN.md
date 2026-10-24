# Plan de mejora · CineFlow

Rama del plan: `plan/mejoras` (sale de `main`). Reglas comunes: `plan/REGLAS.md`. Dirección visual: `plan/DISENO.md`. Avisos para Ayman: `plan/AVISOS.md`.

## Objetivo

Que la demo sea una taquilla oscura, en español, donde elegir butaca sea claro y accesible con teclado y lector de pantalla, y que el pago con Stripe en modo de pruebas quede terminado: la reserva se confirma por webhook firmado, de forma idempotente, y las butacas se liberan si el pago caduca o falla. Sin romper nada de lo que cuenta el caso del portafolio y sin reescribir el trabajo de Ismael y Danna más allá de lo visual.

## Modelos

Todas las sesiones se ejecutan en opencode go:

| Clave | Modelo (paseo) | Para qué |
|---|---|---|
| Flash | `opencode-go/glm-5.3-flash`, razonamiento `high` | tareas mecánicas y acotadas |
| GLM | `opencode-go/glm-5.3`, razonamiento `high` | vistas en muchos archivos, criterio visual, JavaScript de la rejilla |
| Pro | `opencode-go/deepseek-v4-pro`, razonamiento `max` | pagos |
| Revisión | `opencode-go/gpt-6-luna`, razonamiento `xhigh` | revisión final |

Después de la revisión, Opus solo revisa el resultado final.

## Sesiones

| NN | Sesión | Modelo | Depende de | Estado |
|---|---|---|---|---|
| 00 | Entorno local y CI en cada PR | Flash | | hecha (PR #1, fusionado con 3 pruebas en rojo que ya fallaban en `main`; arregladas en 00b) |
| 00b | Pruebas de la línea base en verde | | 00 | hecha (PR #2) |
| 01 | Auditoría (solo informe) | Flash | 00, 00b | hecha (PR #3) |
| 02 | Tokens de diseño y layout base | GLM | 01 | hecha (PR #4) |
| 03 | Cartelera y ficha de película | GLM | 02 | hecha (PR #5) |
| 04 | Selector de butacas accesible | GLM | 02 | hecha (PR #6) |
| 04b | Bloqueos de invitados | | 00b | hecha (PR #7) |
| 05 | Stripe Checkout con webhook | Pro | 00 | hecha (PR #8) |
| 06 | Accesibilidad global, rendimiento y textos | GLM | 03, 04, 05 | hecha (PR #9) |
| 07 | Revisión final | Revisión | 06 | pendiente |

## Orden y paralelo

```
00 ─┬─► 01 ─► 02 ─┬─► 03 ─┐
    │             └─► 04 ─┼─► 06 ─► 07
    └─► 05 ───────────────┘
```

- 03 y 04 van a la vez (vistas distintas). 05 va en paralelo desde el principio: es backend y la vista del paso 3.
- 04 y 05 tocan `compra/_sidebar.blade.php` y `CompraController.php`. 04 solo toca la vista y el JavaScript del paso 2; 05 el paso 3, el retorno y el webhook. La segunda en fusionar resuelve conflictos conservando ambos cambios.

## Terminado significa

- Las ocho sesiones fusionadas en `plan/mejoras` con su PR y su verificación en verde.
- `php artisan test` con 38 pruebas o más (las de 05 suman), ninguna saltada, en CI.
- axe sin violaciones `serious` ni `critical` en portada, cartelera, ficha, cines, los tres pasos de compra, confirmación, login, registro, mis entradas y panel de cliente, a 390 y 1440 px.
- Elegir butacas se puede hacer solo con teclado y se entiende con lector de pantalla.
- Con claves de Stripe de prueba, una compra con `4242 4242 4242 4242` termina en reserva confirmada por webhook; un pago abandonado libera las butacas. (Si no hay claves, queda probado con dobles y con un aviso.)
- README actualizado (sin la frase de demo «pending deployment»; con la demo real y el flujo de pago).
- Capturas finales en `plan/capturas/final/`.
- Ayman revisa el PR `plan/mejoras → main` y lo fusiona él.

Cuando todo esté hecho, la sesión 04 del portafolio usa las capturas finales y actualiza el caso (el punto 4 de su `docs/PENDIENTES.md`: cómo se conecta Stripe con la reserva).
