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

### Imágenes propias (sesión 10)

Cuatro imágenes **generadas con IA por Ayman Charoui** para CineFlow. No son fotos de stock ni retratan a nadie. Viven en `public/img/marca/fotos/` en AVIF y WebP con varios anchos (`NOMBRE-ANCHO.avif`), se generan con `plan/herramientas/optimizar-imagenes.mjs` y se pintan con `<x-marca.imagen nombre="…" />` (`srcset`, `sizes`, `width` y `height`; las medidas están en `config/marca.php`). Los pósteres reales siguen mandando: ninguna imagen de marca se pone sobre ellos.

| Imagen | Nombre | Dónde va | Cómo |
|---|---|---|---|
| Sala vacía con butacas rojas y pantalla blanca (16:9) | `sala` | Hero de la portada y fondo de la ficha cuando la película no tiene fondo propio (`Pelicula::tiene_fondo`); fondo de «Elige la butaca. El resto es cine.»; fondo de acceso y registro | Siempre oscurecida (opacidad de .3 a .8 y velos). En acceso y registro el formulario va en una tarjeta opaca, así que el contraste no depende de la foto |
| Haz de proyector sobre negro (16:9) | `haz` | Portada y ficha de película | Capa `mix-blend-mode: screen`. GSAP la gira sobre su foco (`movimiento/haz.js`, 11 s, ida y vuelta) y la pausa fuera de pantalla. Con `prefers-reduced-motion` queda quieta. En el hero con la sala no se pinta: la sala ya trae el suyo |
| Butaca roja bajo un foco (4:5) | `butaca` | «Cómo se compra», paso 2 («Tu butaca queda guardada 8 minutos») | Banda recortada sobre el mapa, con el sitio y la cuenta atrás sobre un velo oscuro. En escritorio con movimiento, cuando carga la ilustración de Rive (ver «Piezas de Rive»), ocupa su lugar; la foto sigue en móvil, con movimiento reducido y si el runtime no carga |
| Vestíbulo de noche con mostrador rojo y cajas de luz (16:9) | `vestibulo` | Cabecera de «Cines» | Es la imagen LCP de esa página: sin `loading="lazy"` y con `fetchpriority="high"` |

- Solo la imagen del hero sin fondo propio (portada) y la cabecera de «Cines» cargan con prioridad; el resto es `loading="lazy"`.
- Atribución: imágenes generadas con IA por Ayman Charoui.

## Movimiento

Principios:

1. **La luz se enciende, no aparece.** El título de la portada se enciende letra a letra como una marquesina, con una fila de bombillas que se turnan.
2. **La sala se abre.** Dos bandas panorámicas se retiran al cargar la portada; un haz de proyector (la imagen `haz`) barre la escena y un grano de película la recorre.
3. **La cartelera pasa como película.** En escritorio, la portada y la cartelera son una sola escena fijada: el hero se encoge hasta ser el primer fotograma de una tira de celuloide con perforaciones y el scroll la hace avanzar. En móvil es un carrusel táctil con perforaciones.
4. **Lo tuyo responde.** La butaca se hunde al pulsarla y al quedar elegida; la entrada sale como si se imprimiera.
5. **El pago no se adorna.** En la compra solo hay microinteracciones de respuesta (la butaca se hunde). Nada de GSAP, Lenis ni transiciones de página.

Dónde vive cada cosa:

| Pieza | Archivo |
|---|---|
| Estilos del movimiento (solo páginas públicas) | `resources/css/movimiento.css` |
| Chunk de GSAP, ScrollTrigger, SplitText y Lenis | `resources/js/movimiento/` (se carga solo si el layout declara `@section('mov')`) |
| Portada: hero y escena fijada | `portada.js` |
| Haz de proyector (portada y ficha) | `haz.js` |
| «Cómo se compra» en cuatro pasos | `compra.js` y `resources/views/portada/` |
| Nombre en contorno que se rellena | `nombre.js` |
| Inclinación 3D con foco, botones magnéticos, logotipo del pie | `micro.js` |
| Transiciones entre páginas | `@view-transition` en `movimiento.css` (View Transitions API, sin JavaScript) |
| Piezas de Rive (logotipo, ilustración de compra, anillo) | `resources/js/rive/` y `rive/` (ver «Piezas de Rive») |

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

## Móvil y espacios (sesión 13)

**Móvil y táctil** (`(max-width: 1023px), (pointer: coarse)`) tiene su propia coreografía, más corta y sin pins:

- Portada: el hero no se encoge ni se fija. Entra un 40 % más rápido y, al salir, la imagen se acerca un 8 % y el contenido se desvanece, con el scroll mandando directamente (`scrub: true`, sin la inercia del escritorio).
- El haz se funde y se queda quieto; el grano no se anima; el rojo telón del hero es un color plano en vez de una mezcla `multiply`; sin perspectiva 3D en los pósteres ni `will-change` en los botones. Lenis solo con ratón.
- La tira de celuloide es un carrusel táctil con `scroll-snap-type: x mandatory`. Las cuatro ilustraciones de «Cómo se compra» también (la siguiente asoma a la derecha) y se animan una vez, cuando su tarjeta entra en pantalla (IntersectionObserver): la portada mide 3,7 mil px a 390 px en vez de 4,8 mil.
- `tests/browser/movil.spec.mjs` lo comprueba a 390×844 y 360×780.

**Escala de espaciado vertical** (tokens de Tailwind sobre variables CSS, `app.css`): `bloque` (20 a 32 px, entre bloques de una sección), `pagina` (24 a 40 px, relleno vertical de una página) y `seccion` (40 a 64 px, entre secciones y antes del pie). El hueco entre el último contenido y el pie queda entre 64 y 104 px en todas las páginas públicas (antes, de 120 a 160 px).

## Piezas de Rive (sesión 11)

Tres piezas hechas con la CLI de Rive 1.8.0 y pintadas con el runtime `@rive-app/canvas-lite` (MIT). Los fuentes son **solo RML, sin scripts** (nada de `.luau` ni `.wgsl`) y viven en `rive/<nombre>/`; los `.riv` construidos, en `public/rive/`. `npm run rive` los reconstruye todos y copia el WebAssembly del runtime a `public/rive/rive.wasm` (necesita `rive` en el PATH; la CLI vive en `~/.rive/bin`). El runtime se carga con `import()` solo cuando una pieza entra en pantalla y su `.wasm` sale de nuestro dominio, sin CDN.

| Pieza | Fuente | `.riv` | Dónde | Cómo se maneja |
|---|---|---|---|---|
| Logotipo animado | `rive/logo` | 4,5 KB | Cabecera de la portada y logotipo grande de `/marca` | Al cargar, una vez, y al pasar el ratón o el foco. En táctil se queda el SVG |
| Ilustración de «Cómo se compra» | `rive/compra` | 16,7 KB | Portada, escena fijada de escritorio (pasos 2 a 4) | Entrada numérica `progreso` (0 a 100) del view model, que escribe el ScrollTrigger de `movimiento/compra.js` |
| Anillo de la cuenta atrás | `rive/anillo` | 0,9 KB | Compra, pasos 2 y 3 | Entrada numérica `resto` (segundos, 0 a 480) que escribe `cuenta-atras.js` cada segundo |

- **Logotipo.** La butaca suelta los brazos y el asiento y se queda en el respaldo, que es la entrada; los cuatro troqueles saltan, las dos mitades se separan por la línea de perforaciones y vuelven a juntarse; la butaca vuelve y el reposo es exactamente el símbolo de `<x-marca.simbolo>`. Dura 2 s (una máquina de estados con un disparador `repetir`).
- **Ilustración de compra.** La butaca se ilumina bajo el haz y se marca como tuya (marquesina); su anillo de 8:00 se vacía hasta 2:00; entra la tarjeta, un barrido la valida y se marca; sale la entrada impresa por la ranura, con el símbolo y la línea de corte. No lleva texto (la versión lite no pinta texto de Rive): la cuenta atrás es HTML encima del lienzo y los pasos son los de siempre.
- **Anillo.** Marquesina sobre pista `sala-700`; en el último minuto pasa a `carmin-400` **de golpe**, porque un fundido entre amarillo y rojo pasa por el naranja. La punta gira con el arco.
- **Accesibilidad.** Los lienzos llevan `aria-hidden` y no reciben foco. El número de la cuenta atrás, su `role="timer"` y los avisos de 5, 2, 1 minuto y 30 segundos siguen en HTML. El mapa de butacas no se ha tocado.
- **Movimiento reducido.** No se descarga el runtime: se ve el SVG del símbolo, las vistas HTML de «Cómo se compra» y un anillo SVG que se vacía en saltos de un segundo (es información, como el número).
- **Si el runtime falla.** Queda el SVG, las vistas HTML y el anillo SVG, sin errores en consola.
- **Alineación del bloque de texto de «Cómo se compra».** Al fijarse la escena de golpe (un salto de scroll), GSAP movía `.compra-pista` al borde izquierdo porque llevaba `margin: 0 auto`. Ahora el elemento fijado va a todo el ancho y el contenido se centra en `.compra-rejilla`, con el mismo ancho y margen que la cabecera.
- **Peso.** Runtime: 58 KB de JavaScript y 807 KB de WebAssembly (332 KB comprimido), una sola vez para las tres piezas. Archivos `.riv`: 22 KB en total.

## Usos correctos e incorrectos

| Correcto | Incorrecto |
|---|---|
| Símbolo en rojo telón sobre `sala-950` o `sala-900` | Símbolo en marquesina (ese amarillo es solo para lo tuyo) |
| Monocromo `texto` sobre fotos oscurecidas | Logotipo sobre una foto sin oscurecer |
| Reducido por debajo de 32 px | Perforaciones a 16 px (se empastan) |
| Nombre en Big Shoulders 900 desde `config('app.name')` | Redibujar el nombre en otra fuente, estirarlo o inclinarlo |
| Rojo telón en rótulos de 24 px o más | Rojo telón en texto pequeño (usa `carmin-400`) |
| Separar símbolo y nombre en espacios pequeños | Cambiar las proporciones del símbolo, añadir sombras o degradados |
