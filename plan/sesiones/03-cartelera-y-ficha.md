# 03 · Cartelera y ficha de película

Modelo: GLM 5.3 (opencode go), razonamiento high
Rama: `mejora/03-cartelera`, desde `plan/mejoras`. Nunca `main`.
Depende de: 02 fusionada. Puede ir en paralelo con 04.
Skills que lee antes de empezar:
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/frontend-ui-engineering/SKILL.md`
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/taste-skill/SKILL.md` (composición y jerarquía; `plan/DISENO.md` manda)

## Contexto

Lee `plan/REGLAS.md`, `plan/DISENO.md` y las secciones de accesibilidad y textos de `plan/AUDITORIA.md`. Vistas: `resources/views/home.blade.php` (portada, 376 líneas, con titular de película destacada), `peliculas/index.blade.php` (cartelera con filtros, 318), `peliculas/show.blade.php` (ficha con sesiones, 159), `cines/index.blade.php` y `cines/show.blade.php`. Controladores: `HomeController`, `PeliculaController` (trabajo de Ayman), `CineController`. Las sesiones de una película vienen del modelo `Sesion` (trabajo de Ismael): **no cambies su lógica ni sus consultas**, solo cómo se pintan.

## Tareas

1. Crea los componentes Blade:
   - `components/movie-card.blade.php` (`@props(['pelicula'])`): `<article>` cuyo título es el enlace a `peliculas.show` y que hace clicable toda la tarjeta con el truco del `::after` absoluto (no envuelvas la tarjeta entera en `<a>`). Póster 2:3 con `width="300" height="450"`, `loading="lazy"`, `alt="Póster de {título}"`, borde `sala-700`, `rounded-card`. Debajo: título (`font-semibold text-lg`), duración y clasificación en `text-texto-2 cifras`, géneros como etiquetas (`rounded-full border border-sala-700 px-2 text-xs text-texto-2`).
   - `components/showtime.blade.php` (`@props(['sesion'])`): enlace con aspecto de botón a `compra.step1` con `sesion_id`; hora `cifras text-lg font-semibold`, debajo sala y formato; `aria-label="Comprar para las {hora}, sala {n}"`.
2. **Portada** (`home.blade.php`): titular de la película destacada con su póster como fondo **difuminado y oscurecido** (`blur-2xl opacity-30`) detrás, y el póster nítido a la derecha (1440 px) o arriba (390 px). Título con `.display` en 4 rem (2,5 rem en 390 px), sinopsis recortada a 3 líneas (`line-clamp-3`), géneros, duración y botón «Ver sesiones» (carmín) hacia la ficha. Debajo, «En cartelera» con una rejilla de `movie-card` (2 columnas en 390, 5 en 1440). Quita etiquetas en mayúsculas espaciadas («NOW SHOWING») y pon «En cartelera».
3. **Cartelera** (`peliculas/index.blade.php`): filtros en un `<form role="search">` con etiquetas visibles («Buscar», «Género», «Cine»); botón «Filtrar» y enlace «Quitar filtros». Resultado anunciado con `aria-live="polite"` («12 películas»). Rejilla de `movie-card`. Estado vacío con texto y enlace para quitar filtros.
4. **Ficha** (`peliculas/show.blade.php`): arreglo del desbordamiento horizontal a 390 px (lo marca la auditoría). Cabecera con póster (izquierda en 1440, arriba en 390), título `.display` 3 rem, ficha técnica en un `<dl>` (duración, clasificación, géneros, estreno, director si existe). Sinopsis en `max-w-prose`. Sesiones agrupadas por día (`<h2>` «Hoy», «Mañana», «sábado 24») y dentro por cine, con `showtime`. Si no hay sesiones futuras: «No hay sesiones programadas» y enlace a la cartelera.
5. **Cines** (`cines/index.blade.php`, `cines/show.blade.php`): tarjetas con nombre, dirección (`x-icon name="map-pin"`) y salas; en la ficha del cine, las películas con sus sesiones usando los mismos componentes.
6. Textos de estas vistas en español. Fechas con `->locale('es')->isoFormat('dddd D [de] MMMM')` y horas `H:mm`.
7. Prueba de vista en `tests/Feature/CarteleraViewTest.php`: `GET /peliculas` responde 200 y contiene «En cartelera» o el `aria-live`; `GET /peliculas/{id}` de una película con una sesión futura (usa las factorías existentes) contiene un enlace a `/comprar?sesion_id=`. No cambies las pruebas existentes.

## No hacer

- No cambies consultas, scopes ni modelos de `Sesion`, `Sala` o `Pelicula`; si necesitas un dato que la vista no recibe, calcúlalo en la vista o pídelo con un aviso.
- No toques la compra (04 y 05).

## Criterios de aceptación

- Sin desplazamiento horizontal a 390 px en ninguna de estas vistas.
- axe sin `serious`/`critical` en `/`, `/peliculas`, `/peliculas/{id}`, `/cines`, `/cines/{id}` a 390 y 1440 px.
- La prueba nueva pasa y el total de pruebas sube.

## Verificación

Ver `plan/REGLAS.md`. Capturas en `plan/capturas/03/`.

## Cierre

Commit(s), push, PR hacia `plan/mejoras` con capturas antes/después; fusión si está en verde; marca 03 como hecha.
