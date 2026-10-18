# Dirección de diseño · CineFlow

Decidida el 2026-10-18 tras revisar la demo (`plan/capturas/antes/`) y el código (`resources/css/app.css`, `tailwind.config.js`, `resources/views/layout.blade.php`, `resources/views/compra/step2.blade.php`). Cada decisión lleva su porqué en una línea.

## Qué hay hoy y qué cambia

Hoy la web es **clara** (fondo `#f7f4f1` con degradado rosado), con barra superior roja con enlaces falsos (Promotions, Contact, redes con `href="#"`), Figtree desde fonts.bunny.net, Font Awesome desde un CDN, textos de la interfaz en inglés con la cartelera en español, y un mapa de butacas de 22 px con cuatro colores (gris, verde, rojo y **naranja**) sin otra diferencia que el color.

Cambia a: **una taquilla de sala oscura donde la butaca es la protagonista.** Fondo casi negro, un rojo carmín de terciopelo para las acciones y el amarillo de la luz de la marquesina solo para lo que es tuyo. Todo autoalojado y en español.

Se conserva: el logotipo de texto «CINE FLOW», el flujo de compra en tres pasos con su barra de progreso, la estructura de la cartelera y la ficha.

## Capturas de referencia (hoy)

| Pantalla | 1440 px | 390 px |
|---|---|---|
| Portada | `capturas/antes/home-1440.png` | `capturas/antes/home-390.png` |
| Cartelera | `capturas/antes/cartelera-1440.png` | `capturas/antes/cartelera-390.png` |
| Ficha de película | `capturas/antes/pelicula-1440.png` | `capturas/antes/pelicula-390.png` (desborda en horizontal) |
| Compra, paso 1 | `capturas/antes/compra1-1440.png` | `capturas/antes/compra1-390.png` |
| Login | `capturas/antes/login-1440.png` | `capturas/antes/login-390.png` |

El mapa de butacas (paso 2) no se capturó para no bloquear butacas en la demo pública: la sesión 01 lo captura en local.

axe sobre la demo (`capturas/antes/axe-demo.json`): botones sin nombre (menú móvil y `+`/`−` de entradas), `select` y campos sin etiqueta en la cartelera y el paso 1, 70 fallos de contraste en la ficha, y desplazamiento horizontal en la ficha a 390 px.

## Paleta

| Token | Valor | Uso | Contraste comprobado |
|---|---|---|---|
| `sala-950` | `#0E0D10` | fondo de página | (fondo) |
| `sala-900` | `#18161C` | superficies: tarjetas, paneles, mapa de butacas | (superficie) |
| `sala-850` | `#221F27` | superficie elevada, hover de filas | decorativo, separa por borde |
| `sala-700` | `#3A3641` | bordes y divisores | decorativo |
| `texto` | `#F2EEE6` | texto principal | 16,7:1 sobre `sala-950` |
| `texto-2` | `#A8A3AE` | texto secundario, etiquetas | 7,9:1 sobre `sala-950`; 7,3:1 sobre `sala-900` |
| `carmin-600` | `#B3203A` | botón principal (relleno) | texto blanco 6,6:1 |
| `carmin-700` | `#95182F` | botón principal al pasar el ratón | texto blanco 8,6:1 |
| `carmin-400` | `#F0566B` | carmín como texto o borde sobre oscuro (enlaces activos, precio, filete) | 5,8:1 sobre `sala-950`; 5,3:1 sobre `sala-900` |
| `marquesina` | `#EBD67C` | **solo** butaca tuya, resumen de tu selección, foco | 12,3:1 sobre `sala-900`; texto `sala-950` sobre él 13,3:1 |
| `foco` | `#F5E6A8` | contorno de foco | 15,5:1 sobre `sala-950` |
| `ocupada` | `#57525F` | relleno de butaca vendida | 2,4:1 (componente deshabilitado; lleva ✕ en `#E4E0EA` a 5,8:1) |
| `ok` | `#6FD39A` | confirmación, pago completado | 9,8:1 sobre `sala-900` |
| `error` | `#FF7A85` | mensajes de error, pago fallido | 7,2:1 sobre `sala-900` |

Por qué:
- Fondo casi negro con un toque violáceo apagado: es la sala con las luces bajadas; el póster y la butaca resaltan solos.
- Carmín para actuar: es el terciopelo de la butaca y ya es el rojo de la marca CineFlow (hoy `#d4183d`); se oscurece para que el texto blanco pase de 4,5:1.
- Amarillo de marquesina **solo** para «lo tuyo»: en un mapa de 200 butacas, tu selección tiene que verse al primer vistazo y no confundirse con nada más. Es amarillo (matiz ~50°), no naranja.
- Se elimina el verde de «seleccionada» y el rojo de «vendida» de hoy: rojo/verde es la pareja que peor distinguen las personas daltónicas, y el rojo ya significa «acción».
- Sin modo claro: el encargo es oscuro y una sala no tiene modo claro.

## Estados de la butaca (lo más importante)

Cada estado se distingue por **forma, icono y texto**, no solo por color. Butaca de 32 × 32 px (36 × 36 en 390 px para el dedo), esquinas superiores de 8 px e inferiores de 3 px (perfil de butaca), número visible dentro.

| Estado | Relleno | Borde | Marca | Texto accesible | ¿Se puede pulsar? |
|---|---|---|---|---|---|
| Libre | transparente | 2 px `#8C8794` (5,1:1) | número | «Fila F, butaca 7, libre» | sí |
| Tuya | `marquesina` | 2 px `marquesina` | ✓ y número en `sala-950` | «Fila F, butaca 7, seleccionada por ti» | sí (la suelta) |
| Retenida por otra persona | rayado diagonal `#8C8794` al 35 % | 2 px discontinuo `#8C8794` | icono de candado 12 px | «Fila F, butaca 7, retenida por otra persona» | no |
| Vendida | `ocupada` | ninguno | ✕ en `#E4E0EA` | «Fila F, butaca 7, vendida» | no |
| Con foco | la de su estado | contorno 2 px `foco` con separación de 2 px | | | |

La leyenda encima del mapa muestra las cuatro muestras **con el mismo dibujo** que en el mapa y su nombre.

## Tipografía

| Rol | Fuente | Pesos | Por qué |
|---|---|---|---|
| Títulos y logotipo | Archivo Variable, ancho 75 (condensada), con `@fontsource-variable/archivo` (`wdth.css`) | 700, 800 | Condensada y alta como las letras de una marquesina; una sola familia para todo. |
| Interfaz y texto | Archivo Variable, ancho 100 | 400, 600 | La misma familia en ancho normal: coherente, legible y con cifras tabulares para precios y horas. |

- Se quitan Figtree de fonts.bunny.net y Font Awesome del CDN de cdnjs: dos terceros menos, sin salto de fuente. Los iconos pasan a SVG en línea (componente Blade `x-icon`).
- Horas, precios, filas y butacas: `font-variant-numeric: tabular-nums` (clase `cifras`).
- Títulos en mayúsculas solo en el logotipo y en la etiqueta «PANTALLA» del mapa; el resto en caja normal (hoy casi todo va en mayúsculas espaciadas y cuesta leer).

Escala (rem): 0,8125 · 0,875 · 1 · 1,25 · 1,5 · 2 · 3 (títulos de película) · 4 (titular de portada a 1440 px; 2,5 en 390 px). Interlineado 1,5 en texto, 1,1 en títulos.

## Espaciado, radios y elevación

- Base 4 px (escala de Tailwind). Pasos: 1, 2, 3, 4, 6, 8, 12, 16, 24.
- Tarjeta: `p-6` (24 px), `p-4` en 390 px. Entre secciones: 80 px (`py-20`), 56 px en 390 px.
- Radios: 8 px controles y botones (no píldoras: hoy todo es píldora y los botones parecen etiquetas), 12 px tarjetas y paneles, 999 px solo etiquetas de género.
- Sin sombras negras sobre negro (no se ven). La elevación es un borde `sala-700` y la superficie `sala-900`. El póster lleva un borde de 1 px `sala-700`.
- Brillo carmín (`box-shadow: 0 0 0 1px #F0566B33, 0 8px 24px #B3203A33`) solo en el botón principal de cada paso de compra.

## Foco y estados

- Foco: `outline: 2px solid #F5E6A8; outline-offset: 2px;` con `:focus-visible` en todo. Por qué: el amarillo claro destaca sobre negro y sobre carmín.
- Botón principal deshabilitado (por ejemplo «Continuar» sin butacas): relleno `sala-850`, texto `texto-2`, `aria-disabled` y una frase debajo que explica por qué («Elige 2 butacas para continuar»).
- Errores de formulario: borde `error`, mensaje con icono debajo, `aria-invalid` y `aria-describedby`.
- `prefers-reduced-motion`: sin escalado de butacas al pasar el ratón, sin transiciones de página, la cuenta atrás sigue pero sin animación.

## Componentes que se rediseñan

Componentes Blade en `resources/views/components/` (los de Breeze se reaprovechan y se reestilan):

| Componente | Archivo | Qué cambia |
|---|---|---|
| Botón | `primary-button`, `secondary-button`, `danger-button` | radios de 8 px, carmín / borde `sala-700` / borde `error`; alto 44 px |
| Campo | `text-input`, `input-label`, `input-error` | fondo `sala-900`, borde `#8C8794`, etiqueta visible siempre |
| Icono | `icon.blade.php` (nuevo) | SVG en línea por nombre: `ticket`, `menu`, `close`, `lock`, `check`, `x`, `clock`, `user`, `logout`, `film`, `map-pin`, `calendar`, `arrow-left`, `arrow-right` |
| Tarjeta de película | `movie-card.blade.php` (nuevo) | póster 2:3 con `width`/`height`, título, duración, clasificación, géneros; toda la tarjeta es un enlace |
| Sesión | `showtime.blade.php` (nuevo) | botón con hora en `cifras` y sala; si quedan menos de 10 butacas, «Quedan 8» en `texto-2` |
| Cabecera y pie | `layout.blade.php` | sin barra roja superior ni redes falsas; logotipo, «Cartelera», «Cines», «Mis entradas» o «Entrar», y «Comprar entradas» en carmín |
| Progreso de compra | `compra/_progress.blade.php` | `<ol>` con `aria-current="step"`, pasos «Entradas», «Butacas», «Pago» |
| Resumen lateral | `compra/_sidebar.blade.php` | póster, película, cine, sala, día y hora, entradas, butacas en `marquesina`, total en `cifras` y la cuenta atrás del bloqueo |
| Mapa de butacas | `compra/step2.blade.php` + `resources/js/seat-map.js` (nuevo) | rejilla accesible (ver sesión 04) |

## Lo que no se hace

- Ningún mapa 3D ni perspectiva de sala: la rejilla plana se lee mejor y es accesible.
- Nada de imágenes de stock. Los pósteres son los de la cartelera (TMDB o los generados por `GeneratePosterPlaceholders`).
- No se toca el panel de administración más allá de que herede el tema oscuro y sea legible (sesión 06).
