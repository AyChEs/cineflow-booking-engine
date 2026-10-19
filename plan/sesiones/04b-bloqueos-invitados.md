# 04b · Bloqueos de invitados

Rama: `mejora/04b-bloqueos-invitados`, desde `plan/mejoras`. Nunca `main`.
Depende de: 00b. Hallazgo de la sesión 04 (al probar el mapa).

## Problema

`CompraController` buscaba los bloqueos «de otros» con `where('user_id', '!=', $miId)` (o `session_token != $miToken` para invitados). Un bloqueo de invitado tiene `user_id` nulo y en SQL `NULL != 5` no es verdadero, así que para quien tiene sesión iniciada esa butaca aparecía libre: `lockSeat` hacía `updateOrCreate` sobre la fila del invitado y se la quedaba, y `step2Store` la aceptaba. Contradice lo que cuenta el caso (el bloqueo temporal impide que otra persona coja la butaca).

## Arreglo

Un método privado `deOtros()` en `CompraController` que incluye los nulos (`whereNull('user_id')->orWhere('user_id', '!=', $id)`, y lo mismo con `session_token` para invitados), usado en `step2`, `step2Store`, `seatStatus` y `lockSeat`. No cambian rutas, campos, el modelo `SeatLock` ni los 8 minutos.

## Pruebas

`tests/Feature/BloqueoInvitadoTest.php`: un usuario no puede quitar la butaca a un invitado (ni al revés), el estado la marca retenida y el paso 2 la rechaza. Sin el arreglo fallan 3 de 4.
