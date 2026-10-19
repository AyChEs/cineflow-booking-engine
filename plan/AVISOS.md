# Avisos para Ayman

Aquí las sesiones dejan solo lo que necesita una decisión o una acción tuya. Formato en `plan/REGLAS.md`.

## 2026-10-18 · Sesión 00 · Tres pruebas ya fallaban antes de tocar nada
- Qué pasa: la línea base da 38 pruebas: 35 pasan, 3 fallan y 0 se saltan. Fallan `PasswordResetTest` (la sesión no guarda `reset_link` tras pedir el enlace) y dos de `PurchaseFlowTest` (`user can complete purchase flow` y `race condition prevents double booking`: la reserva no llega a crearse, así que el test de carrera tampoco llega a demostrar el bloqueo). Es anterior a esta sesión y el job `tests` del CI sale en rojo en cada PR hasta que se resuelva.
- Opciones: 1) arreglar las tres pruebas en una sesión propia antes de la 05 (la 05 añade más pruebas y las superpondría a un flujo roto); 2) dejarlo como está y apilar sesiones encima de un CI rojo.
- Recomiendo: 1, porque el test de carrera fallando toca lo que afirma el portafolio (la confirmación con `lockForUpdate` y 3 intentos) y es justo el flujo que la sesión 05 va a rehacer con Stripe.
- Queda bloqueado: fusionar cualquier PR con la verificación en verde mientras estas tres pruebas sigan fallando (las reglas lo prohíben). El PR de la sesión 00 queda abierto sin fusionar.
