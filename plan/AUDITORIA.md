# Auditoría · CineFlow (sesión 01)

Fecha: 2026-10-19. Contra la app en local (`php artisan serve`, base `migrate:fresh --seed`), rama `plan/mejoras` tras la sesión 00. Capturas y `report.json` en `plan/capturas/01/`. Esta sesión no cambia la aplicación.

Notas del entorno donde se hizo:
- Los pósteres de TMDB, Font Awesome (cdnjs) y Figtree (fonts.bunny.net) no se pueden descargar desde esta máquina (el proxy los bloquea). Para que las capturas se lean, la base **local** usa pósteres neutros generados al vuelo (no se versionan). Los iconos de Font Awesome no se ven en las capturas por el mismo motivo; en la demo sí.
- Lighthouse mide sin esos terceros (fallan al momento), así que el rendimiento real en la demo será algo peor que el medido.

## Urgente

1. **El mapa de butacas no respeta la sala.** `CompraController::computeLayout` (`app/Http/Controllers/CompraController.php:577`) solo entiende `vip`, `premium` y `standard`; las salas del seeder usan `FxC` (`database/seeders/SalaSeeder.php:14`: `8x10`, `10x12`…), así que todas caen en 14 butacas por fila. Una sala `8x10` se pinta como 6 filas de 14 (A–F) y las butacas vendidas por `ReservaSeeder` (`database/seeders/ReservaSeeder.php:123`, que sí usa `8x10`: A–H × 1–10) en las filas G y H no aparecen; en cambio se ofrecen butacas que no existen (C12). Añadido a la sesión 04.
2. **Naranja prohibido en estados de reserva.** `#f39c12` para «pendent» en `resources/views/cliente/dashboard.blade.php:84` y `resources/views/reservas/show.blade.php:43`, además del `#d97706` ya conocido de `compra/step2.blade.php:48` y `:256`. Y `GeneratePosterPlaceholders` (`app/Console/Commands/GeneratePosterPlaceholders.php:14`) pinta pósteres con naranja (`Fantasía`, `Aventura`…) y degradados morados por defecto. Los dos primeros, a la sesión 06; el comando, a la 06 también (solo paleta).

## 1. Resumen

| Pantalla | Serias/críticas a 390 | Serias/críticas a 1440 | Desplazamiento horizontal |
|---|---|---|---|
| Portada `/` | button-name (8) | button-name (4) | no |
| Cartelera `/peliculas` | button-name (1), label (2), select-name (2) | label (2), select-name (1) | no |
| Ficha `/peliculas/1` | button-name (1), color-contrast (41) | color-contrast (41) | no en local (sí en la demo, con pósteres reales) |
| Cines `/cines` | button-name (1), link-name (3) | link-name (3) | no |
| Cine `/cines/1` | button-name (1) | ninguna | no |
| Login | button-name (1) | ninguna | no |
| Registro | button-name (1) | ninguna | no |
| Mis entradas `/mis-reservas` | button-name (1) | aria-prohibited-attr (1), color-contrast (11) | no |
| Panel de cliente `/dashboard/client` | button-name (1) | color-contrast (5) | no |
| Perfil `/profile` | button-name (1), color-contrast (2) | ninguna | **sí** |
| Admin películas `/admin/peliculas` | button-name (1) | color-contrast (8) | no |
| Compra, paso 1 | button-name (1), color-contrast (10), label (4) | color-contrast (10), label (4) | no |
| Compra, paso 2 | button-name (1), color-contrast (39) | color-contrast (39), **target-size (79)** | no |
| Compra, paso 3 | button-name (1), color-contrast (11) | color-contrast (11) | no |

El `button-name (1)` de todas las pantallas a 390 px es el botón del menú móvil (`#mobileMenuToggle`, `resources/views/layout.blade.php:77`).

Lighthouse (local, sin terceros):

| Página | Rendimiento | Accesibilidad | Buenas prácticas | SEO | LCP |
|---|---|---|---|---|---|
| `/` móvil | 94 | 93 | 96 | 92 | 2,2 s |
| `/` escritorio | 100 | 93 | 96 | 92 | 0,5 s |
| `/peliculas/1` móvil | 92 | 82 | 96 | 92 | 2,5 s |
| `/peliculas/1` escritorio | 100 | 94 | 96 | 92 | 0,6 s |

CLS 0 en las cuatro. El SEO pierde puntos por `<html lang="en">` con contenido en español (`layout.blade.php:2`) y enlaces sin texto.

## 2. Accesibilidad

De axe:
- **Menú móvil sin nombre** (`layout.blade.php:77`): botón solo con icono. Arreglo: `aria-label="Abrir menú"`, `aria-expanded`, `aria-controls`, panel con `inert` cerrado, Esc cierra y devuelve el foco (sesión 02).
- **Portada**: botones del carrusel con `aria-label` en inglés (`home.blade.php:170`, `:212`) y otros botones sin texto (8 a 390 px). Arreglo: rejilla de tarjetas sin carrusel (sesión 03).
- **Cartelera**: `select` de orden y de día sin etiqueta (`peliculas/index.blade.php:28`, `:146`), búsqueda sin `<label>` (`:57`). Arreglo: etiquetas visibles dentro de un `<form role="search">` (sesión 03).
- **Ficha**: 41 fallos de contraste (texto gris claro sobre fondo claro con `cinema-muted`, `peliculas/show.blade.php`). Arreglo: tokens oscuros (02) y ficha nueva (03).
- **Cines**: la tarjeta entera es un `<a>` vacío superpuesto (`cines/index.blade.php:23`), sin texto: `link-name`. Arreglo: el nombre del cine es el enlace y el `::after` cubre la tarjeta (03).
- **Mis entradas**: `aria-label` en un elemento que no lo admite y contraste (`usuarios/misReservas.blade.php`). Sesión 06.
- **Perfil**: desborda a 390 px y tiene contraste bajo (`profile/edit.blade.php`). Sesión 02.
- **Paso 1**: los campos de cantidad no tienen etiqueta (`compra/step1.blade.php:145`) y los botones `−`/`+` (`:143`, `:149`) solo tienen el símbolo. Sesión 04.
- **Paso 2**: butacas de 22 px (`target-size`, 79 nodos a 1440 px), leyenda y subtítulo con contraste bajo. Sesión 04.
- **Paso 3**: textos de 0,65 rem en gris claro. Sesión 05.

A mano:
- **Mapa de butacas con teclado**: son `<button>` en una tabla (`compra/step2.blade.php:314`), se llega a cada uno con Tab (más de 80 paradas), sin flechas. Los estados se distinguen solo por color (gris, verde, rojo y naranja). El nombre accesible es solo el número («12»), sin fila ni estado. Las vendidas son `disabled` y no reciben foco: con lector de pantalla no se sabe que existen.
- **Selección al límite**: si ya tienes todas las butacas, elegir otra suelta la más antigua sin avisar (`step2.blade.php:396`).
- **Cuenta atrás del bloqueo**: no existe. Nada en la vista dice que las butacas se reservan 8 minutos; al caducar, el usuario se entera al enviar el paso 2 o el 3.
- **Errores de formulario**: mensajes en `<p>` sin `aria-describedby` ni `aria-invalid` (login, registro, paso 3); el foco no va al primer error.
- **`lang`**: `<html lang="en">` en `layout.blade.php:2`.
- Sin enlace «Saltar al contenido» ni `<main>` con id.

## 3. Textos en inglés

Vistas con textos de interfaz en inglés (grep `>[A-Z][a-z]+( [a-z]+)+` y revisión):

- Layout y comunes: `layout.blade.php` (barra superior «Promotions», «Contact», navegación «Now showing», «Cinemas», «Log in», «Buy tickets», menú «Discover», «My Account», «Box Office», «Administration»).
- Públicas: `home.blade.php`, `peliculas/index.blade.php`, `peliculas/show.blade.php`, `cines/show.blade.php`.
- Compra: `compra/_sidebar.blade.php`, `compra/_progress.blade.php`, `compra/step1.blade.php`, `compra/step2.blade.php`, `compra/step3.blade.php`, `compra/confirmacio.blade.php`, y los mensajes de `CompraController` («Please select a session first.», «Select at least 1 ticket.»…) y las etiquetas de `CompraController::TIPUS` («Adult», «Reduced», «Family», «Senior +65»).
- Cuenta: `auth/login`, `auth/register`, `auth/forgot-password`, `auth/reset-password`, `auth/confirm-password`, `auth/verify-email`, `auth/dashboard`, `profile/edit`, `cliente/dashboard`, `usuarios/misReservas`, `usuarios/reservar`, `reservas/show`, `emails/entrada`.
- Admin: `peliculas/admin-index`, `peliculas/create`, `peliculas/edit`, `peliculas/external-index`, `peliculas/external-show`, `cines/admin-index`, `salas/create`, `salas/index`, `sesiones/create`, `sesiones/edit`, `sesiones/show`.

## 4. Estilos

Bloques `<style>` dentro de vistas: `home.blade.php:119`, `compra/step1.blade.php:5`, `compra/step2.blade.php:7`, `compra/step3.blade.php:8`, `compra/confirmacio.blade.php:22`, `usuarios/reservar.blade.php:322`. Además muchos `style="…"` en línea en `auth/*`, `auth/dashboard.blade.php`, `profile/edit.blade.php`.

Colores escritos a mano más usados (vistas y CSS): `#9ca3af` (29), `#1e3a5f` (23, azul marino de Stripe/pago), `#fff` (19), `#e74c3c` (17), `#ffffff` (11), `#ef4444` (7), `#e5e7eb` (7), `#1f2937` (7), `#dc2626` (6), `#27ae60` (6), `#152a45` (6), `#d1d5db` (5), `#b91c1c` (4), `#16a34a` (4), `#111827` (4), `#f39c12` (3, **naranja**), `#2d6a9f` (3), `#22c55e` (3), `#d97706` (2, **naranja**), `#1a1a2e`, `#16213e`, `#0f3460` (2 cada uno). Todos deben pasar a los tokens de `plan/DISENO.md`.

Clases `cinema-*` (`tailwind.config.js:19`): `cinema-accent` en `peliculas/index` (13), `peliculas/external-show` (7), `peliculas/external-index` (6), `home` (6), `peliculas/create` (5), `peliculas/show` (5), `cines/create` (4), `usuarios/index` (3), `sesiones/edit` (1); `cinema-surface` en `peliculas/show` (5), `peliculas/external-show` (2), `sesiones/create`, `sesiones/edit`, `salas/create`; `cinema-muted` en `peliculas/create` (6), `peliculas/show` (5), `home` (2), `peliculas/external-show` (2), `salas/create`; `cinema-bg` en `peliculas/create` (2).

## 5. Terceros

- Font Awesome 6.5.1 desde cdnjs (`layout.blade.php:8`): 25 vistas con `fa-*`. Más usados: `fa-ticket-alt` (9), `fa-film` (9), `fa-arrow-left` (9), `fa-exclamation-circle` (8), `fa-check-circle` (7), `fa-star` (6), `fa-times` (5), `fa-plus` (5), `fa-lock` (5), `fa-search` (4), `fa-save` (4), `fa-qrcode` (4), `fa-map-marker-alt` (4). Marcas: `fa-cc-visa`, `fa-cc-mastercard`, `fa-cc-amex`, `fa-google-pay`, `fa-twitter`, `fa-instagram`, `fa-facebook-f` (las tres redes en la barra superior con `href="#"`).
- Figtree desde fonts.bunny.net (`layout.blade.php:9-10`).
- `html5-qrcode` desde jsdelivr en `taquilla/scanner.blade.php:52` (se queda: es la cámara del escáner; valorar autoalojarlo en 06).
- `placehold.co` como póster de reserva en `peliculas/external-show.blade.php:19`, `peliculas/external-index.blade.php:123` y `peliculas/create.blade.php:55` (admin).
- Stripe.js en `compra/step3.blade.php:414` (desaparece con Checkout en 05).
- Pósteres de TMDB (`image.tmdb.org`), legítimos.

## 6. Flujo de compra y bloqueos

Rutas en `routes/web.php:51-74`.

1. **Paso 1** `GET /comprar?sesion_id=` → `CompraController::step1` (`:29`): libera los bloqueos previos del usuario (`releaseLocks`, `:469`) y borra la compra de la sesión. `POST /comprar/entrades` → `step1Store` (`:54`): calcula el total en el servidor (`preu_base × factor` por tipo, `TIPUS` en `:21`), máximo 10 entradas, guarda `compra` en la sesión.
2. **Paso 2** `GET /comprar/butaques` → `step2` (`:92`): `SeatLock::clearExpired()`, butacas vendidas (`ReservaSeat`), retenidas por otros y propias; pinta el mapa con `computeLayout` (`:577`, ver «Urgente»). En el navegador (`compra/step2.blade.php:384`) cada clic llama a `POST /api/seat-lock` (`lockSeat`, `:513`, crea o renueva un `SeatLock` de 8 minutos con `updateOrCreate` sobre la clave única `sesion_id + butaca`) o a `POST /api/seat-unlock` (`unlockSeat`, `:558`). Límite: 30 peticiones por minuto. El mapa sondea `GET /api/seats/{id}` (`seatStatus`, `:484`) **cada 10 s** (`step2.blade.php:444`). `POST /comprar/butaques` → `step2Store` (`:140`): comprueba número, vendidas y retenidas, borra los bloqueos del usuario y los vuelve a crear con 8 minutos nuevos (`:196`).
3. **Caducidad**: no hay aviso ni cuenta atrás. Los bloqueos caducados se borran de forma perezosa en cada consulta (`SeatLock::clearExpired`, `app/Models/SeatLock.php:24`) y con el comando `CleanupExpiredSeatLocks` (no programado en `routes/console.php`). Si caducan mientras el usuario está en el paso 3, la compra sigue (no se comprueba que el bloqueo siga vivo) y la confirmación solo falla si otro ya compró esa butaca.
4. **Paso 3** `GET /comprar/pagament` → `step3` (`:215`).
   - **Sin Stripe** (`StripeService::isEnabled()` falso: falta `STRIPE_SECRET`): formulario de tarjeta simulada con Luhn y caducidad; `POST /comprar/pagament` → `step3Store` (`:279`) → `finalizeReservation` (`:363`).
   - **Con Stripe**: Payment Element embebido; `POST /comprar/stripe/intent` → `stripeIntent` (`:242`) crea un **PaymentIntent** con el total de la sesión; el navegador lo confirma y vuelve a `GET /comprar/stripe/return` → `stripeReturn` (`:334`), que consulta el PaymentIntent y, si está `succeeded`, **confirma la reserva desde la vuelta del navegador**. Si el navegador se cierra tras pagar, no hay reserva; recargar puede intentar confirmar dos veces (lo frena la clave única de `reserva_seats`, pero el pago ya está cobrado); un pago fallido o abandonado deja las butacas retenidas hasta que caducan.
5. **Confirmación** `finalizeReservation` → `PurchaseService::confirmPurchase` (`app/Services/PurchaseService.php:22`): transacción con `Sesion::lockForUpdate()`, comprueba cada butaca, crea `Reserva` (`estat = pagat`) y `ReservaSeat` (clave única `sesion_id + butaca`, `database/migrations/2026_04_14_000002_create_reserva_seats_table.php:34`), hasta 3 intentos. Después borra los bloqueos, envía la entrada con QR (`enviarEntrada`, `:419`) y redirige a `GET /comprar/confirmacio` (`:439`).

## 7. Autoría

Según `docs/evidencias/fase-16-19.md`: Películas, Ayman; Sesiones, Ismael; Salas, Danna; Categorías e integración, todo el equipo. Compra, Stripe, QR, despliegue y CI: Ayman, después del proyecto.

| Archivo que tocará el plan | Autoría | Qué se permite |
|---|---|---|
| `layout.blade.php`, `home.blade.php`, componentes, `auth/*`, `profile/*` | equipo / Breeze | todo lo del plan |
| `peliculas/*`, `PeliculaController` | Ayman | todo lo del plan |
| `cines/*`, `CineController` | equipo | todo lo del plan |
| `compra/*`, `CompraController`, `PurchaseService`, `StripeService` | Ayman | todo lo del plan (sin cambiar `confirmPurchase` ni los 8 minutos) |
| `sesiones/*`, `SesionController`, `Sesion` | **Ismael** | solo cambios visuales |
| `salas/*`, `SalaController`, `Sala` | **Danna** | solo cambios visuales |
| `reservas/*`, `usuarios/*`, `cliente/*`, `taquilla/*` | equipo / Ayman | visual y textos (06) |

`computeLayout` está en `CompraController` (no en `Sala`): arreglarlo no toca el trabajo de Danna.

## 8. Propuesta por sesión

- **02**: tema oscuro y tokens, Archivo autoalojada, `x-icon` en lugar de Font Awesome, cabecera y pie nuevos (sin barra roja ni redes falsas), menú móvil accesible, `lang="es"`, saltar al contenido, componentes de Breeze y vistas `auth/*`/`profile/*` en español. Resuelve `button-name` del menú en todas las pantallas, el desbordamiento de `/profile` y la mayoría de contrastes.
- **03**: portada sin carrusel, cartelera con filtros etiquetados, ficha sin desbordamiento, cines con enlaces con nombre. Resuelve `label`, `select-name`, `link-name` y los 41 contrastes de la ficha.
- **04**: paso 1 con controles con nombre, mapa en rejilla accesible (estados con forma y texto, 32/36 px), cuenta atrás anunciada, sin naranja, y el arreglo de `computeLayout`.
- **05**: Stripe Checkout con webhook firmado e idempotente, liberación de butacas, paso 3 nuevo (con la opción de Bizum simulado que ya acepta el servidor desde 00b).
- **06**: confirmación, mis entradas, panel de cliente, admin y taquilla legibles en oscuro, sin naranja (`#f39c12`, pósteres generados), textos restantes, `<h1>` y títulos, prueba `a11y` en CI, Lighthouse.
