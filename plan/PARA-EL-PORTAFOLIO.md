# Para el portafolio (caso CineFlow)

Lo que el plan de mejora **amplía** respecto a lo que cuenta hoy el caso. Nada de lo anterior deja de ser cierto. Frases cortas, para la sesión 04 del portafolio.

## Pago con Stripe

- El pago es Stripe Checkout en modo de prueba.
- La reserva no la confirma el navegador al volver: la confirma un webhook firmado.
- Cada evento de Stripe se procesa una sola vez, aunque llegue repetido.
- Mientras alguien paga, sus butacas siguen bloqueadas: el bloqueo se amplía hasta que caduca el pago.
- Si el pago caduca, falla o se cancela, las butacas se liberan al momento.
- Si otra compra se queda la butaca un instante antes, el pago se reembolsa solo.
- Los importes se calculan en el servidor, en céntimos.
- Sin claves de Stripe, la demo usa un pago simulado (tarjeta o Bizum).

## Concurrencia

- Se corrigió un fallo: una butaca bloqueada por un invitado aparecía libre para quien tenía sesión iniciada y podía quitársela. Ahora no.
- El mapa respeta la forma real de cada sala (filas × columnas).

## Accesibilidad

- El mapa de butacas es una rejilla que se usa solo con teclado: flechas, Inicio, Fin y Espacio.
- Cada butaca dice su estado con forma, icono y texto, no solo con color.
- Un lector de pantalla oye qué butaca eliges y cuántas te faltan.
- La cuenta atrás del bloqueo se anuncia a los 5, 2 y 1 minutos y a los 30 segundos.
- Una prueba con axe recorre 13 pantallas a 390 y 1440 px en cada PR.

## Cifras

- 76 pruebas con PHPUnit (eran 38), más dos pruebas de navegador.
- Lighthouse móvil: accesibilidad 100 en portada, cartelera y ficha.

## Capturas

`plan/capturas/final/`: portada, cartelera, ficha, mapa de butacas y entrada, a 390 y 1440 px.
