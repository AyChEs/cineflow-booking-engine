# Avisos para Ayman

Aquí las sesiones dejan solo lo que necesita una decisión o una acción tuya. Formato en `plan/REGLAS.md`.

## 2026-10-18 · Sesión 00 · Tres pruebas ya fallaban antes de tocar nada
- Qué pasa: la línea base da 38 pruebas: 35 pasan, 3 fallan y 0 se saltan. Fallan `PasswordResetTest` (la sesión no guarda `reset_link` tras pedir el enlace) y dos de `PurchaseFlowTest` (`user can complete purchase flow` y `race condition prevents double booking`: la reserva no llega a crearse, así que el test de carrera tampoco llega a demostrar el bloqueo). Es anterior a esta sesión y el job `tests` del CI sale en rojo en cada PR hasta que se resuelva.
- Opciones: 1) arreglar las tres pruebas en una sesión propia antes de la 05 (la 05 añade más pruebas y las superpondría a un flujo roto); 2) dejarlo como está y apilar sesiones encima de un CI rojo.
- Recomiendo: 1, porque el test de carrera fallando toca lo que afirma el portafolio (la confirmación con `lockForUpdate` y 3 intentos) y es justo el flujo que la sesión 05 va a rehacer con Stripe.
- Queda bloqueado: fusionar cualquier PR con la verificación en verde mientras estas tres pruebas sigan fallando (las reglas lo prohíben). El PR de la sesión 00 queda abierto sin fusionar.

## 2026-10-19 · Sesión 00b · Resuelto: las tres pruebas de la línea base
- Qué pasa: resuelto, solo informativo. Comprobado que fallaban ya en `main`; el PR #1 se fusionó anotándolo y la sesión 00b (`plan/sesiones/00b-pruebas-base.md`) arregla la causa raíz sin tocar ninguna prueba. Había una cuarta intermitente (`AuthenticationTest`, por el rol al azar de la factoría).
- Opciones: ninguna pendiente.
- Recomiendo: si quieres que la demo de Render muestre el enlace de recuperación en pantalla, pon `AUTH_DEMO_RESET_LINK=true` solo sabiendo que cualquiera podría cambiar la contraseña de cualquier cuenta. Por defecto queda apagado en producción.
- Queda bloqueado: nada.

## 2026-10-19 · Sesión 05 · Claves de Stripe en modo de prueba
- Qué pasa: el pago con Stripe Checkout y el webhook firmado están hechos y probados con una pasarela doble (sin llamar a Stripe), pero en este entorno no hay claves, así que falta la prueba real de punta a punta. Sin las dos claves la web usa el pago simulado (tarjeta o Bizum), como hoy.
- Opciones: 1) poner las claves de prueba y crear el webhook (pasos abajo); 2) dejar la demo con el pago simulado.
- Recomiendo: 1, porque el caso del portafolio cuenta Stripe en modo de prueba. Pasos:
  1. En https://dashboard.stripe.com/test/apikeys (modo de prueba) copia la clave publicable (`pk_test_…`) y la secreta (`sk_test_…`).
  2. En Desarrolladores → Webhooks, crea un endpoint hacia `https://cineflow-ufe0.onrender.com/stripe/webhook` con los eventos `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed` y `checkout.session.expired`; copia su secreto de firma (`whsec_…`).
  3. En Render (Environment) pon `STRIPE_KEY`, `STRIPE_SECRET` y `STRIPE_WEBHOOK_SECRET` (`render.yaml` ya declara las tres con `sync: false`). No las escribas en el repositorio.
  4. Prueba: compra una entrada y paga con `4242 4242 4242 4242`, cualquier fecha futura y cualquier CVC; la página «Confirmando tu pago…» debe llevarte a la entrada. Luego empieza otra compra, pulsa «Volver» en Stripe y comprueba que las butacas vuelven a estar libres.
  5. En local: `stripe listen --forward-to localhost:8000/stripe/webhook` da el `whsec_…` para tu `.env`.
- Queda bloqueado: solo la prueba real de punta a punta con Stripe.

## 2026-10-24 · Sesión 06 · Formularios del panel de administración (opcional)
- Qué pasa: los formularios de alta y edición del panel (películas, cines, salas, sesiones, usuarios y reservas) ya se leen bien en oscuro, están en español y axe no da violaciones, pero sus errores no llevan `aria-invalid`/`aria-describedby` ni mueven el foco al primero, como sí hacen los de la parte pública. Varios son de Ismael (sesiones) y Danna (salas).
- Opciones: 1) pasarlos al componente `x-campo` en una sesión propia; 2) dejarlos así: solo los usa el personal.
- Recomiendo: 2 por ahora, porque el caso del portafolio es la compra; 1 si el panel se va a enseñar.
- Queda bloqueado: nada.

## 2026-10-24 · Sesión 07 · Frente CineFlow terminado
- Qué pasa: las sesiones 00 a 07 (más 00b y 04b) están fusionadas en `plan/mejoras` con el CI en verde (`tests` y `a11y`). Revisión en `plan/REVISION.md`, resumen para el caso en `plan/PARA-EL-PORTAFOLIO.md`, capturas en `plan/capturas/final/`. Por tu indicación no se ha abierto el PR `plan/mejoras → main`.
- Opciones: 1) abrir y fusionar tú el PR `plan/mejoras → main` («Tema de sala, butacas accesibles y pago con Stripe Checkout»); 2) revisarlo antes en local con `migrate:fresh --seed`.
- Recomiendo: 2 y luego 1. Al fusionar en `main`, Render desplegará: antes pon las claves de Stripe (aviso de la sesión 05) o la demo seguirá con el pago simulado. Las migraciones nuevas (`checkout_pagos`, `stripe_eventos`) se aplican con el `migrate` del arranque.
- Queda bloqueado: nada del plan; solo la prueba real con Stripe.
- Ramas: no he podido borrar las ramas `mejora/*` fusionadas desde este entorno (el proxy rechaza el borrado). Puedes borrarlas en GitHub.
