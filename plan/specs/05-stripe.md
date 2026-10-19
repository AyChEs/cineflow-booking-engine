# Especificación · Sesión 05 · Stripe Checkout con webhook firmado

Origen: `plan/sesiones/05-stripe-checkout.md`. Esta especificación concreta las decisiones; si algo choca, manda la sesión.

## Objetivo

Que un pago con Stripe (modo de prueba) termine en una reserva confirmada **solo** por un webhook firmado, de forma idempotente, y que las butacas se liberen si el pago caduca, falla o se cancela. Sin claves de Stripe, el pago simulado sigue igual.

## Qué no cambia

- `SeatLock`, los 8 minutos para elegir butacas y `PurchaseService::confirmPurchase` (transacción, `lockForUpdate`, 3 intentos). El webhook la llama; no se reescribe.
- Rutas y campos del paso 1 y del paso 2.

## Piezas

| Pieza | Archivo | Responsabilidad |
|---|---|---|
| Contrato de pasarela | `app/Contracts/PasarelaPago.php` | `crearSesionCheckout`, `expirarSesion`, `reembolsar`. Las pruebas usan un doble sin red. |
| Implementación | `app/Services/StripePasarela.php` | `StripeClient` con clave de idempotencia en cada escritura. |
| Activación | `app/Services/StripeService.php` | `isEnabled()` exige `STRIPE_SECRET` **y** `STRIPE_WEBHOOK_SECRET`. |
| Pago | `app/Models/CheckoutPago.php`, tabla `checkout_pagos` | Estado del pago y datos para crear la reserva. |
| Eventos | `app/Models/StripeEvento.php`, tabla `stripe_eventos` | Idempotencia por `event_id` (único). |
| Lógica | `app/Services/CheckoutService.php` | Crear el pago, confirmar, reembolsar, liberar butacas. Un solo sitio para las transiciones. |
| Entrada por correo | `app/Services/EnvioEntrada.php` | El `enviarEntrada` de antes, compartido por el pago simulado y el webhook. |
| HTTP | `app/Http/Controllers/PagoController.php`, `app/Http/Controllers/StripeWebhookController.php` | Paso 3 con Stripe, vuelta, estado, cancelar; webhook. |
| Red de seguridad | comando `checkout:limpiar`, cada 5 minutos en `routes/console.php` | Pagos abiertos caducados hace más de 5 minutos. |

## Tabla `checkout_pagos`

`id`, `stripe_session_id` (único, nulo al principio), `sesion_id` (FK), `butacas` (JSON), `tipus_entrada` (tipo dominante, como hoy), `entradas` (JSON: cantidad por tipo), `total` (céntimos, entero), `email`, `nombre`, `user_id` (nulo), `session_token`, `estado`, `reserva_id` (nulo), `expires_at`, timestamps. Índices: `estado + expires_at` (limpieza).

## Estados y transiciones

| Desde | Evento | Hacia | Efecto |
|---|---|---|---|
| (nuevo) | «Pagar» en el paso 3 | `abierto` | Crea la Checkout Session; amplía los `SeatLock` hasta `expires_at` + 2 min; redirección 303 a Stripe. Un pago `abierto` anterior del mismo comprador y la misma sesión pasa a `caducado` (y se expira en Stripe). |
| `abierto` | `checkout.session.completed` con `payment_status = paid` | `pagado` | `confirmPurchase`; guarda `reserva_id`; borra sus `SeatLock`; envía la entrada. |
| `abierto` | `checkout.session.async_payment_succeeded` | `pagado` | Igual. |
| `abierto` | cualquiera de los dos anteriores, pero `confirmPurchase` lanza `SeatAlreadyReservedException` | `reembolsado` | Reembolso con clave `reembolso-{id}`; borra sus `SeatLock`; registro en el log `audit`. |
| `abierto` | `checkout.session.completed` con `payment_status` distinto de `paid` | `abierto` | Nada: se espera al evento asíncrono. |
| `abierto` | `checkout.session.expired` | `caducado` | Borra sus `SeatLock`. |
| `abierto` | `checkout.session.async_payment_failed` | `fallido` | Borra sus `SeatLock`. |
| `abierto` | «Cancelar» en Stripe (`/comprar/stripe/cancel`) | `caducado` | Expira la sesión en Stripe (ignora el error si ya no está abierta) y borra sus `SeatLock`. |
| `abierto` | `checkout:limpiar` con `expires_at` + 5 min pasado | `caducado` | Borra sus `SeatLock`. |
| `pagado`, `reembolsado`, `caducado`, `fallido` | cualquiera | sin cambio | Nada (idempotente). |

Cada transición bloquea la fila del pago (`lockForUpdate`) dentro de una transacción y solo actúa si el estado es `abierto`.

## Webhook `POST /stripe/webhook`

1. Fuera de CSRF (`bootstrap/app.php`). Sin sesión de usuario ni autenticación.
2. Firma: `\Stripe\Webhook::constructEvent($payload, $firma, STRIPE_WEBHOOK_SECRET)`. Firma inválida, ausente o sin secreto configurado: **400** y nada más. Antes de verificar no se lee el cuerpo.
3. En una transacción: inserta `stripe_eventos(event_id, tipo, procesado_en)`. Si ya existe (clave única), **200** sin hacer nada. Después procesa según la tabla. Si algo falla (por ejemplo, la llamada de reembolso), la transacción se deshace, incluido el evento, y responde 500 para que Stripe reintente.
4. El pago se busca por `metadata.checkout_pago_id` y debe coincidir `stripe_session_id`; si no, 200 y un aviso en el log (no es nuestro).
5. El correo de la entrada se envía después del commit.

## Navegador

- **Paso 3 con Stripe** `POST /comprar/stripe/checkout`: valida nombre y correo; comprueba que ninguna butaca está vendida ni retenida por otro (si lo está, vuelve al paso 2 con un mensaje); importes calculados en el servidor desde `preu_base` y el factor de cada tipo (céntimos enteros); nada del importe sale del cliente.
- **Vuelta** `GET /comprar/stripe/return?session_id=…`: no confirma nada. Muestra «Confirmando tu pago…» y sondea `GET /comprar/estado/{checkoutPago}` cada 2 s durante 60 s. Con `pagado`, va a la confirmación; con `reembolsado`, explica el reembolso; con `caducado` o `fallido`, vuelve al paso 2.
- **Estado** `GET /comprar/estado/{checkoutPago}` (JSON `{estado, reserva_url}`): solo para quien inició el pago (`user_id` si hay sesión iniciada; si no, `session_token`). Si no, **403**. Con `pagado` deja en la sesión los datos de la pantalla de confirmación.
- **Cancelar** `GET /comprar/stripe/cancel`: el pago abierto de esta sesión pasa a `caducado` y vuelve al paso 2.

## Importes

Precio por entrada en céntimos: `round(preu_base × factor × 100)`. Total = suma de precio × cantidad. El paso 1 calcula el total de la misma forma para que lo mostrado y lo cobrado coincidan siempre (antes se redondeaba por línea y podía diferir en un céntimo).

## Pruebas (`tests/Feature/StripeCheckoutTest.php`)

Con un doble de `PasarelaPago` y la firma calculada de verdad (`t={ts},v1=hmac_sha256("{ts}.{payload}", secreto)`):

1. Pagar crea un `CheckoutPago` `abierto`, llama a la pasarela con el total correcto en céntimos y amplía los `SeatLock` hasta `expires_at` + 2 min.
2. Firma inválida: 400 y nada cambia.
3. `checkout.session.completed` crea una reserva con sus `ReservaSeat` y deja el pago `pagado`.
4. El mismo evento dos veces: una sola reserva.
5. Dos eventos distintos `completed` para el mismo pago: una sola reserva.
6. `checkout.session.expired` libera los bloqueos y otra persona puede bloquear esas butacas.
7. Butaca ya vendida: `completed` reembolsa (el doble registra la llamada) y deja `reembolsado`.
8. La vuelta nunca crea reservas.
9. El estado de un pago ajeno devuelve 403.
10. Sin `STRIPE_WEBHOOK_SECRET`, el paso 3 muestra el pago simulado.

Además: cancelar libera las butacas; `checkout:limpiar` caduca los pagos abandonados.

## Claves (no van en el repositorio)

`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` en `.env` local o en Render. Endpoint del webhook en el panel de Stripe hacia `https://cineflow-ufe0.onrender.com/stripe/webhook` con `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed` y `checkout.session.expired`.
