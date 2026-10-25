# Marca · CineFlow

Sistema de marca de la taquilla. Parte de `plan/DISENO.md` (sala oscura, carmín para actuar, marquesina solo para lo tuyo) y le da lo que le faltaba: un símbolo propio, una tipografía con carácter de cartelería, un color de marca valiente y un motivo que se repite. Página viva del sistema: `/marca`. Archivos: `docs/marca/` y `public/img/marca/`.

## Concepto

**La butaca es la entrada.** En un cine lo que compras no es una película, es un sitio en la oscuridad: fila F, butaca 7, a las 22:15. CineFlow es la taquilla de ese momento, y la marca se construye con los tres objetos que lo cuentan: la butaca, la entrada perforada y el haz del proyector.

Frase de marca: **«Elige la butaca. El resto es cine.»** (`config('marca.lema')`).

## Personalidad

- **De sala:** oscura, cálida, con la luz justa donde hace falta.
- **Precisa:** hora, sala, fila y butaca antes que adjetivos.
- **Con oficio:** carteles, marquesinas y entradas, no la estética de una app genérica.
- **Tranquila al pagar:** el espectáculo se queda en la cartelera; en la compra, nada distrae.

## Voz

Tuteo, frases cortas, datos concretos. Sin exclamaciones, sin rayas largas, sin «robusto», «soluciones» ni «apasionado».

| Sí | No |
|---|---|
| «Tu butaca queda guardada 8 minutos.» | «¡Reserva ya tu asiento ideal!» |
| «Fila F, butaca 7. Sala 3, 22:15.» | «Disfruta de una experiencia única.» |
| «Elige 2 butacas para continuar.» | «Ups, algo ha ido mal.» |

## Logotipo

| Pieza | Archivo | Uso |
|---|---|---|
| Símbolo | `simbolo.svg` | avatar, sellos, favicon grande, entradas |
| Símbolo reducido (sin perforaciones) | `docs/marca/simbolo-reducido.svg` | por debajo de 32 px |
| Horizontal | `logo-horizontal.svg` | cabecera, pie, documentos sobre oscuro |
| Horizontal sobre claro | `logo-horizontal-sobre-claro.svg` | papel, fondos claros |
| Monocromo | `logo-mono-claro.svg`, `logo-mono-oscuro.svg`, `simbolo-mono-*.svg` | una tinta, grabados, marcas de agua |
| Favicon | `favicon.svg`, `favicon.ico`, `apple-touch-icon.png` | pestaña y pantalla de inicio |
| Imagen para redes | `og.png` (1200 × 630) | `og:image` |

**El símbolo.** Una butaca de sala vista de frente: respaldo, brazos y asiento. El respaldo es a la vez una entrada: dos muescas semicirculares en los costados y una línea de cuatro perforaciones entre ellas, la línea por la que se corta el talón. Se lee como butaca a 16 px y como entrada en cuanto crece.

**Construcción** (retícula de 64 u):

- Respaldo de 34 × 34 u (x 15 a 49, y 6 a 40), radio superior 13 u e inferior 3 u.
- Muescas de radio 4,5 u centradas a la altura 24; perforaciones de radio 1,8 u en x 22, 28,67, 35,33 y 42.
- Brazos de 6 u de ancho con remate redondo; asiento de 14 u de alto con radio inferior de 5 u.
- Separación constante de 4 u entre respaldo, brazos y asiento: es lo que da aire al símbolo a cualquier tamaño.

**El nombre.** Big Shoulders Display 900 en mayúsculas, con la altura de las versales igual a la del símbolo menos los brazos. En la web no es una imagen: el componente `<x-marca.logo />` pinta el símbolo y escribe `config('app.name')`. Cambiar el nombre es cambiar `APP_NAME` y regenerar los SVG de `public/img/marca/`; el símbolo no cambia.

**Área de respeto:** el ancho de una muesca (9 u) alrededor. **Tamaño mínimo:** símbolo de 16 px (versión reducida) y logotipo horizontal de 96 px de ancho.

### Si el nombre vuelve a ser «Lumière»

El símbolo se queda igual y el nombre se escribe LUMIÈRE con la misma letra (`docs/marca/propuesta-lumiere.svg`): basta con `APP_NAME=Lumière` y regenerar los SVG.
La butaca con la entrada encaja aún mejor con «la luz sobre tu sitio», y la acentuación de la È ya está prevista en el área de respeto.

## Paleta

Los tokens de `plan/DISENO.md` siguen. Se añade el rojo telón como color de marca.

| Token | Valor | Uso | Contraste comprobado |
|---|---|---|---|
| `sala-950` | `#0E0D10` | fondo | (fondo) |
| `sala-900` | `#18161C` | superficies | (superficie) |
| `carmin-500` **rojo telón** | `#E0284A` | símbolo, botón principal, rótulos grandes, contorno del nombre | 4,2:1 sobre `sala-950` (gráficos y texto grande); texto blanco encima 4,6:1 |
| `carmin-600` | `#B3203A` | botón al pasar el ratón; símbolo sobre claro | texto blanco 6,6:1; 5,7:1 sobre `#F2EEE6` |
| `carmin-400` | `#F0566B` | carmín como texto pequeño sobre oscuro | 5,8:1 sobre `sala-950` |
| `marquesina` | `#EBD67C` | **solo** lo tuyo (butaca elegida, resumen) y foco | 12,3:1 sobre `sala-900` |
| `texto` | `#F2EEE6` | texto, nombre del logotipo, luz del haz | 16,7:1 sobre `sala-950` |
| `texto-2` | `#A8A3AE` | texto secundario | 7,9:1 sobre `sala-950` |

- **Por qué un rojo más vivo:** el carmín de `DISENO.md` (`#B3203A`) funciona en botones pero apaga la marca en grande. El rojo telón (matiz 349°, lejos del naranja de 15° a 40°) es el terciopelo de la butaca con luz encima.
- **Proporción:** 70 % sala, 20 % texto y luz, 10 % rojo telón. La marquesina no cuenta: aparece solo cuando algo es tuyo.
- **Prohibido:** naranja, terracota y ámbar; degradados morados; el azul marino, el azul y la λ del portafolio; `#0F5E5A` y la arena con ámbar.

## Tipografía

| Rol | Fuente | Pesos | Por qué |
|---|---|---|---|
| Títulos, cartelera, marquesina y nombre | **Big Shoulders Display** (OFL), autoalojada (`resources/fonts/`) | 700, 800, 900 | Nació de la rotulación de Chicago: condensada, alta, de cartel y marquesina. Cabe un título largo en una línea y se reconoce en una captura. |
| Interfaz y texto | **Archivo** (OFL), autoalojada | 400, 600 | Legible, con cifras tabulares para horas y precios. |
| Rótulos en contorno | **Big Shoulders Contorno**: la misma a 900 sin contornos solapados (`big-shoulders-contorno-900.woff2`) | 900 | En contorno, las letras de una fuente variable muestran los trazos internos; esta versión los une. |

Licencias en `docs/terceros/`.

## Motivo gráfico: la perforación

Una sola idea repetida en tres escalas:

1. **Las muescas y la línea de corte** de la entrada (símbolo, entrada con QR, talón).
2. **Las perforaciones de celuloide** arriba y abajo de la tira de pósteres.
3. **Las filas de butacas** del mapa: perforaciones a escala de sala.

Motivo secundario: **el haz del proyector**, un cono de luz `texto` a muy baja opacidad que cruza fondos y carteles.

## Imágenes

- Pósteres reales de TMDB. Si no hay red, carteles sustitutos tipográficos de la marca (`php artisan posters:generate-placeholders`): título grande en Big Shoulders, degradado del color del género, haz, grano y créditos con los datos reales.
- Fondo de la portada y de la ficha: el backdrop de TMDB de la película destacada (`peliculas.fondo_path`), entonado con rojo telón. Sin él, el póster o el fondo del cartel sustituto.
- Nunca fotos de stock ni personas.

## Movimiento

Principios:

1. **La luz se enciende, no aparece.** El título de la portada se enciende letra a letra como una marquesina, con una fila de bombillas que se turnan.
2. **La sala se abre.** Dos bandas panorámicas se retiran al cargar la portada; un haz de proyector barre la escena y un grano de película la recorre.
3. **La cartelera pasa como película.** En escritorio, la portada y la cartelera son una sola escena fijada: el hero se encoge hasta ser el primer fotograma de una tira de celuloide con perforaciones y el scroll la hace avanzar. En móvil es un carrusel táctil con perforaciones.
4. **Lo tuyo responde.** La butaca se hunde al pulsarla y al quedar elegida; la entrada sale como si se imprimiera.
5. **El pago no se adorna.** En la compra solo hay microinteracciones de respuesta (la butaca se hunde). Nada de GSAP, Lenis ni transiciones de página.

Dónde vive cada cosa:

| Pieza | Archivo |
|---|---|
| Estilos del movimiento (solo páginas públicas) | `resources/css/movimiento.css` |
| Chunk de GSAP, ScrollTrigger, SplitText y Lenis | `resources/js/movimiento/` (se carga solo si el layout declara `@section('mov')`) |
| Portada: hero y escena fijada | `portada.js` |
| «Cómo se compra» en cuatro pasos | `compra.js` y `resources/views/portada/` |
| Nombre en contorno que se rellena | `nombre.js` |
| Inclinación 3D con foco, botones magnéticos, logotipo del pie | `micro.js` |
| Transiciones entre páginas | `@view-transition` en `movimiento.css` (View Transitions API, sin JavaScript) |

| Tipo | Duración | Curva |
|---|---|---|
| Respuesta (pulsar, elegir butaca, hover) | 120 a 260 ms | `cubic-bezier(.2, .8, .2, 1)` |
| Entrada de elementos | 600 a 900 ms | `expo.out` |
| Marquesina (por letra) | 55 ms de retardo, 550 ms cada una, con parpadeo | lineal por tramos |
| Bandas panorámicas | 1,15 s | `power3.inOut` |
| Ligado al scroll | lo marca el scroll | lineal, `scrub` de 0,6 a 0,8 s |
| Entrada impresa | 0,9 s en 18 pasos | `steps(18)` |

- Solo `transform` y `opacity`. Lo que parece recorte (la ventana que rellena el nombre, la entrada que sale de la ranura) es un `overflow: hidden` fijo con contenido que se mueve.
- `prefers-reduced-motion: reduce`: no se descarga GSAP ni Lenis, no hay `@view-transition` y todo está en su estado final (título encendido, bandas abiertas, nombre relleno, tira de carrusel).
- Sin JavaScript o si el chunk falla: el contenido sigue visible (el script del layout lo deshace a los 4,5 s).
- El scroll suave de Lenis solo con ratón. Nada bloquea el teclado ni mueve el foco.
- La escena fijada solo se activa en pantallas de 1024 px o más con ratón; en el resto, el hero hace zoom y paralaje al salir y la cartelera es un carrusel.

## Usos correctos e incorrectos

| Correcto | Incorrecto |
|---|---|
| Símbolo en rojo telón sobre `sala-950` o `sala-900` | Símbolo en marquesina (ese amarillo es solo para lo tuyo) |
| Monocromo `texto` sobre fotos oscurecidas | Logotipo sobre una foto sin oscurecer |
| Reducido por debajo de 32 px | Perforaciones a 16 px (se empastan) |
| Nombre en Big Shoulders 900 desde `config('app.name')` | Redibujar el nombre en otra fuente, estirarlo o inclinarlo |
| Rojo telón en rótulos de 24 px o más | Rojo telón en texto pequeño (usa `carmin-400`) |
| Separar símbolo y nombre en espacios pequeños | Cambiar las proporciones del símbolo, añadir sombras o degradados |
