# 05 · Stripe Checkout con webhook firmado

Modelo: DeepSeek V4 Pro (opencode go), razonamiento max
Rama: `mejora/05-stripe`, desde `plan/mejoras`. Nunca `main`.
Depende de: 00 fusionada. Puede ir en paralelo con 01 a 04.
Skills que lee antes de empezar:
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/spec-driven-development/SKILL.md`
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/incremental-implementation/SKILL.md`
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/code-review-and-quality/SKILL.md` (sección de seguridad)

## Contexto

Lee `plan/REGLAS.md` y, si ya existe, «Flujo de compra y bloqueos» de `plan/AUDITORIA.md`.

Hoy (`app/Services/StripeService.php`, `app/Http/Controllers/CompraController.php`): si hay `STRIPE_SECRET`, el paso 3 crea un **PaymentIntent** (`POST /comprar/stripe/intent` → `stripeIntent`) y lo confirma con el Payment Element embebido; al volver (`GET /comprar/stripe/return` → `stripeReturn`) el servidor consulta el PaymentIntent y, si está pagado, **confirma la reserva desde la vuelta del navegador** (`finalizeReservation`, que llama a `PurchaseService::confirmPurchase` con `lockForUpdate` y 3 intentos). Sin claves, hay un pago simulado (`step3Store`). Problemas: si el navegador se cierra tras pagar, no hay reserva; recargar la vuelta puede intentar confirmar dos veces; si el pago falla o se abandona, las butacas quedan bloqueadas hasta que caduquen; y no se usa Stripe Checkout, que es lo que cuentan el README y el portafolio.

Esta parte (Stripe, compra y confirmación) la hizo Ayman después del proyecto de equipo, así que puede cambiarse. `SeatLock` y `PurchaseService` son el corazón del caso: se reutilizan, no se reescriben.

## Diseño que hay que implementar

1. Al pulsar «Pagar» en el paso 3 con Stripe activo:
   - Se crea un registro `CheckoutPago` (tabla nueva `checkout_pagos`: `id`, `stripe_session_id` único y nulo al principio, `sesion_id`, `butacas` (JSON), `tipus_entrada` y desglose de entradas (JSON), `total` (céntimos, entero), `email`, `nombre`, `user_id` nulo, `session_token`, `estado` (`abierto`, `pagado`, `caducado`, `fallido`, `reembolsado`), `reserva_id` nulo, `expires_at`, timestamps).
   - Se crea una **Checkout Session** (`mode=payment`, `line_items` con un precio por tipo de entrada en `price_data`, `currency` de `services.stripe.currency`, `customer_email`, `client_reference_id` = id del `CheckoutPago`, `metadata` con `checkout_pago_id` y `sesion_id`, `success_url` = `route('compra.stripe.return') . '?session_id={CHECKOUT_SESSION_ID}'`, `cancel_url` = `route('compra.stripe.cancel')`, `expires_at` = ahora + 30 minutos (el mínimo de Stripe), y la cabecera de idempotencia `idempotency_key` = `checkout-pago-{id}`).
   - Los `SeatLock` de esas butacas se **amplían** hasta `expires_at` de la sesión + 2 minutos. Los 8 minutos siguen valiendo para elegir; mientras se paga, las butacas siguen retenidas. (Esto amplía lo que cuenta el portafolio: anótalo en el PR para la sesión 04 del portafolio.)
   - Redirección 303 a `session->url`.
2. **Webhook** `POST /stripe/webhook` (fuera de CSRF: añádelo en `bootstrap/app.php` con `validateCsrfTokens(except: ['stripe/webhook'])`), sin sesión ni autenticación:
   - Verifica la firma con `\Stripe\Webhook::constructEvent($payload, $request->header('Stripe-Signature'), config('services.stripe.webhook_secret'))`. Firma inválida o ausente → 400 y nada más.
   - **Idempotencia**: tabla `stripe_eventos` (`event_id` único, `tipo`, `procesado_en`). Si el `event_id` ya existe → 200 sin hacer nada. Inserta el evento y procesa dentro de la misma transacción.
   - `checkout.session.completed` con `payment_status = paid`, y `checkout.session.async_payment_succeeded`: busca el `CheckoutPago` por `metadata.checkout_pago_id` (y comprueba que `stripe_session_id` coincide); si ya está `pagado`, no hace nada; si no, llama a `PurchaseService::confirmPurchase(...)` con sus datos, guarda `reserva_id`, estado `pagado`, borra sus `SeatLock` y envía la entrada (el mismo `enviarEntrada` de hoy, extraído a un servicio si hace falta).
   - Si `confirmPurchase` lanza `SeatAlreadyReservedException` (otra compra ganó): reembolsa con `refunds->create(['payment_intent' => …])` con `idempotency_key` `reembolso-{id}`, estado `reembolsado`, y registra en el log `audit`.
   - `checkout.session.expired` y `checkout.session.async_payment_failed`: estado `caducado` o `fallido` y borra sus `SeatLock` (libera las butacas al momento).
   - Cualquier otro tipo → 200 sin hacer nada.
3. **Vuelta** `GET /comprar/stripe/return?session_id=…`: **no confirma nada**. Muestra «Confirmando tu pago…» con un sondeo a `GET /comprar/estado/{checkoutPago}` (JSON `{estado, reserva_url}`) cada 2 s durante 60 s; con `pagado` redirige a la confirmación; con `reembolsado`, «Alguien compró esas butacas un instante antes. Te hemos devuelto el dinero.»; con `caducado`/`fallido`, vuelta al paso 2. El estado solo lo puede consultar quien inició el pago (comprueba `session_token` o `user_id`). Región `aria-live="polite"` para los cambios.
4. **Cancelar** `GET /comprar/stripe/cancel`: expira la sesión en Stripe (`checkout->sessions->expire`, ignorando el error si ya no está abierta), marca `caducado`, libera los bloqueos y vuelve al paso 2 con un mensaje.
5. **Limpieza**: añade al comando existente `CleanupExpiredSeatLocks` (o a uno nuevo `checkout:limpiar`, programado cada 5 minutos en `routes/console.php`) que los `CheckoutPago` `abierto` con `expires_at` pasado + 5 minutos pasen a `caducado` y liberen sus bloqueos (red de seguridad si un webhook no llega). En Render no hay cron: documenta que el webhook `checkout.session.expired` es la vía principal.
6. **Sin claves de Stripe**, el pago simulado de hoy sigue funcionando igual (confirmación directa). Sin `STRIPE_WEBHOOK_SECRET` pero con `STRIPE_SECRET`, la app no debe ofrecer Stripe: `StripeService::isEnabled()` exige los dos.
7. Se borran `stripeIntent`, la ruta `compra.stripe.intent`, `createPaymentIntent`, `retrievePaymentIntent` y el Payment Element de `step3.blade.php`.

## Tareas

1. Escribe la especificación en `plan/specs/05-stripe.md` (este diseño, con los estados y transiciones de `CheckoutPago` en una tabla y la lista de eventos) y síguela.
2. Introduce una interfaz `App\Contracts\PasarelaPago` con los métodos que necesitas (`crearSesionCheckout`, `expirarSesion`, `reembolsar`) y su implementación `StripePasarela` (usa `StripeClient`). Regístrala en `AppServiceProvider`. Así las pruebas usan un doble sin red.
3. Migraciones nuevas (no toques las existentes): `create_checkout_pagos_table`, `create_stripe_eventos_table`. Modelos `CheckoutPago` y `StripeEvento`.
4. Controlador nuevo `StripeWebhookController` y métodos nuevos en `CompraController` (o un `PagoController` nuevo si queda más limpio) según el diseño.
5. `config/services.php`: `'webhook_secret' => env('STRIPE_WEBHOOK_SECRET')`. `.env.example` y `render.yaml`: añade `STRIPE_WEBHOOK_SECRET` (`sync: false`).
6. Vista del paso 3 (`step3.blade.php`): con Stripe, un resumen y un botón «Pagar {total} con Stripe» (carmín) y la nota «Modo de prueba: usa la tarjeta 4242 4242 4242 4242, cualquier fecha futura y cualquier CVC». Sin Stripe, el formulario simulado de hoy. Textos en español. Página de «Confirmando tu pago…» nueva en `compra/procesando.blade.php`.
7. Pruebas en `tests/Feature/StripeCheckoutTest.php` (con el doble de `PasarelaPago` y la firma calculada de verdad: cabecera `Stripe-Signature: t={ts},v1={hash_hmac('sha256', "{ts}.{payload}", $secret)}`):
   - pagar crea un `CheckoutPago` `abierto`, llama a la pasarela con el total correcto en céntimos y amplía los `SeatLock` hasta `expires_at` + 2 min;
   - webhook con firma inválida → 400 y no cambia nada;
   - `checkout.session.completed` crea **una** reserva con sus `ReservaSeat` y deja el pago `pagado`;
   - el mismo evento dos veces → sigue habiendo una sola reserva;
   - dos eventos distintos `completed` para el mismo pago → una sola reserva;
   - `checkout.session.expired` libera los bloqueos y otra persona puede bloquear esas butacas;
   - si la butaca ya se vendió, `completed` reembolsa (el doble registra la llamada) y deja `reembolsado`;
   - la vuelta (`return`) nunca crea reservas;
   - el estado de un pago ajeno devuelve 403;
   - sin `STRIPE_WEBHOOK_SECRET`, el paso 3 muestra el pago simulado.
   Las 38 pruebas existentes, incluidas `ConcurrentPurchaseTest` y `PurchaseFlowTest`, deben seguir pasando sin cambios (si alguna dependía del Payment Element, adáptala solo en lo imprescindible y explícalo en el PR).
8. README: sección «Payments» nueva (en inglés, como el resto): Checkout, webhook firmado, idempotencia por evento, ampliación del bloqueo mientras se paga, reembolso si otra compra gana, cómo probarlo en local con `stripe listen --forward-to localhost:8000/stripe/webhook`. Actualiza el número de tests.
9. Claves: si no hay `STRIPE_KEY`, `STRIPE_SECRET` y `STRIPE_WEBHOOK_SECRET` de prueba en `.env` local, **no es bloqueante**: todo queda probado con dobles. Deja un aviso en `plan/AVISOS.md` pidiendo a Ayman: crear (o usar) la cuenta de Stripe en modo de prueba, poner las tres variables en Render, y crear el endpoint del webhook en el panel de Stripe hacia `https://cineflow-ufe0.onrender.com/stripe/webhook` con los eventos `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed` y `checkout.session.expired`. Queda bloqueada solo la prueba real de punta a punta.

## No hacer

- No cambies `SeatLock`, la duración de 8 minutos para elegir, ni la lógica de `PurchaseService::confirmPurchase` (puedes llamarla desde otro sitio).
- No toques Sesiones ni Salas.
- No uses claves reales ni de producción, ni las escribas en el repositorio.
- No despliegues ni cambies variables en Render.

## Criterios de aceptación

- Las diez pruebas nuevas pasan y las existentes también; ninguna saltada.
- `grep -rn "PaymentIntent\|stripeIntent\|payment-element" app resources routes` vacío.
- Ninguna ruta que no sea el webhook confirma una reserva pagada con Stripe.

## Verificación

Ver `plan/REGLAS.md`. Capturas del paso 3 y de «Confirmando tu pago…» en `plan/capturas/05/`.

## Cierre

Commit(s), push, PR hacia `plan/mejoras` con la especificación enlazada, la tabla de estados, el recuento de pruebas y la nota para el portafolio («el bloqueo se amplía mientras se paga; la reserva se confirma por webhook firmado e idempotente; si el pago caduca o falla, las butacas se liberan; si otra compra gana, se reembolsa»). Fusión si está en verde; marca 05 como hecha.
