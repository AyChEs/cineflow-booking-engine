# 02 · Tokens de diseño y layout base

Modelo: GLM 5.3 (opencode go), razonamiento high
Rama: `mejora/02-tokens`, desde `plan/mejoras`. Nunca `main`.
Depende de: 01 fusionada.
Skills que lee antes de empezar:
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/frontend-ui-engineering/SKILL.md`
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/incremental-implementation/SKILL.md`

## Contexto

Lee `plan/REGLAS.md`, `plan/DISENO.md` entero y las secciones «Estilos» y «Terceros» de `plan/AUDITORIA.md`. El tema vive hoy en `resources/css/app.css` (variables `--color-*` de un tema claro y muchas clases propias: `.top-bar`, `.header-premium`…) y en `tailwind.config.js` (colores `cinema-*`). El layout común es `resources/views/layout.blade.php` (cabecera, menú móvil y pie); las vistas de Breeze usan componentes de `resources/views/components/`.

Esta sesión cambia el tema a oscuro, autoaloja la tipografía, sustituye Font Awesome por iconos SVG y rehace cabecera, pie y componentes base. Las páginas concretas se rehacen en 03 a 06; aquí basta con que hereden el tema y se lean bien.

## Tareas

1. Fuente: `npm i @fontsource-variable/archivo`. En `resources/css/app.css`, arriba: `@import '@fontsource-variable/archivo/wdth.css';`. En `tailwind.config.js`: `fontFamily.sans: ['"Archivo Variable"', ...defaultTheme.fontFamily.sans]` y `fontFamily.display` igual (el ancho condensado se aplica con la clase `.display`, abajo).
2. Colores en `tailwind.config.js > theme.extend.colors`: sustituye `cinema` por
   ```js
   sala: { 950: '#0E0D10', 900: '#18161C', 850: '#221F27', 700: '#3A3641' },
   texto: { DEFAULT: '#F2EEE6', 2: '#A8A3AE' },
   carmin: { 400: '#F0566B', 600: '#B3203A', 700: '#95182F' },
   marquesina: '#EBD67C', foco: '#F5E6A8', ocupada: '#57525F', libre: '#8C8794',
   ok: '#6FD39A', error: '#FF7A85',
   ```
   y `borderRadius: { control: '8px', card: '12px' }`. Busca los usos de `cinema-` en las vistas y cámbialos por el equivalente (`cinema-bg` → `sala-950`, `cinema-surface` → `sala-900`, `cinema-accent`/`cinema-red` → `carmin-600`, `cinema-muted` → `texto-2`, `cinema-border` → `sala-700`).
3. En `resources/css/app.css` sustituye el bloque `:root` y `body` por el tema oscuro: `:root { color-scheme: dark; }`, `body { @apply bg-sala-950 text-texto font-sans antialiased; }` (sin degradado), y añade:
   ```css
   :focus-visible { outline: 2px solid theme('colors.foco'); outline-offset: 2px; }
   .cifras { font-variant-numeric: tabular-nums; }
   .display { font-family: theme('fontFamily.display'); font-stretch: 75%; font-weight: 800; line-height: 1.1; }
   @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: 0ms !important; scroll-behavior: auto !important; } }
   ```
   Borra las clases de la barra superior (`.top-bar*`, `.social-icons`) y revisa el resto de clases de `app.css`: las que pinten fondos o textos claros pasan a los tokens oscuros.
4. Iconos: crea `resources/views/components/icon.blade.php` con `@props(['name', 'class' => 'w-5 h-5'])` que pinta un `<svg aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">` con el `<path>` según el nombre. Dibuja (o copia de Lucide, licencia ISC; añade `docs/terceros/lucide-LICENSE.txt` si copias) estos: `ticket`, `menu`, `close`, `lock`, `check`, `x`, `clock`, `user`, `logout`, `film`, `map-pin`, `calendar`, `arrow-left`, `arrow-right`, `minus`, `plus`, `search`, `alert`. Sustituye **todos** los `<i class="fa…">` de las vistas por `<x-icon name="…" />` (el equivalente más cercano; si un icono es decorativo y va junto a texto, basta `aria-hidden`; si va solo en un botón, el botón lleva `aria-label`). Quita el `<link>` de Font Awesome de `layout.blade.php` (y de cualquier otra vista: `grep -rn "font-awesome\|fonts.bunny" resources/views`).
5. `layout.blade.php`:
   - Borra la barra superior roja (enlaces «Promotions», «Contact» y redes con `href="#"`: no llevan a nada).
   - Cabecera: `bg-sala-950/90 backdrop-blur border-b border-sala-700`, logotipo «CINE FLOW» con `.display` (CINE en `carmin-400`, FLOW en `texto`), navegación «Cartelera» (`peliculas.index`), «Cines» (`cines.index`), y según sesión «Mis entradas» (`reservas.mis`) y «Salir», o «Entrar». A la derecha, botón «Comprar entradas» (`peliculas.index`) en carmín. Enlace activo: `text-carmin-400` y `aria-current="page"`.
   - Menú móvil: botón con `aria-label="Abrir menú"`, `aria-expanded` y `aria-controls`; panel con `inert` cuando está cerrado; Esc lo cierra y devuelve el foco al botón. Todo en un `<script>` corto al final del layout o en `resources/js/app.js`.
   - Enlace «Saltar al contenido» al principio y `<main id="contenido">`.
   - Pie: `border-t border-sala-700 text-texto-2 text-sm`, «CineFlow · Proyecto final de ciclo de Ayman Charoui, Ismael Achamrouk y Danna Guevara», enlace al repositorio y «Pagos en modo de prueba de Stripe: no se cobra nada».
   - `<html lang="es">`.
6. Reestila los componentes de Breeze en `resources/views/components/`: `primary-button` (carmín, `rounded-control`, alto 44 px, `font-semibold`), `secondary-button` (borde `sala-700`, fondo transparente, hover `sala-850`), `danger-button` (borde y texto `error`), `text-input` (fondo `sala-900`, borde `libre`, texto `texto`, `rounded-control`, alto 44 px), `input-label` (`text-sm font-semibold text-texto`), `input-error` (`text-sm text-error` con `x-icon name="alert"`), `modal`, `dropdown`, `nav-link`, `responsive-nav-link`, `auth-session-status`.
7. Vistas de Breeze (`resources/views/auth/*.blade.php`, `profile/**`): pásalas a español y comprueba que se leen sobre fondo oscuro (sin `bg-white`, `text-gray-900` ni `text-gray-600`: cámbialos por `bg-sala-900` y `text-texto`/`text-texto-2`).

## No hacer

- No rehagas cartelera, ficha, cines ni compra (sesiones 03 y 04), salvo lo mínimo para que se lean sobre fondo oscuro.
- No toques controladores, modelos ni rutas.

## Criterios de aceptación

- `grep -rnE "font-awesome|fonts.bunny|fa-[a-z]|cinema-|#d97706" resources/views resources/css tailwind.config.js` vacío.
- `npm run build` en verde y `php artisan test` igual que la línea base de 00.
- axe sin `serious`/`critical` en `/`, `/login`, `/register` y `/profile` a 390 y 1440 px; el menú móvil se usa con teclado.

## Verificación

Ver `plan/REGLAS.md`. Capturas en `plan/capturas/02/` (portada, login, menú móvil abierto).

## Cierre

Commit(s), push, PR hacia `plan/mejoras` con capturas antes/después; fusión si está en verde; marca 02 como hecha.
