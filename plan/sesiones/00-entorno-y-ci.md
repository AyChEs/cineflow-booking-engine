# 00 · Entorno local y CI en cada PR

Modelo: GLM 5.3 Flash (opencode go)
Rama: `mejora/00-entorno`, desde `plan/mejoras`. Nunca `main`.
Depende de: nada.
Skills que lee antes de empezar:
- `/home/ayman/Documentos/proyectos/portafolio/.claude/skills/verification-before-completion/SKILL.md`

## Contexto

Lee `plan/REGLAS.md`. Esta máquina no tiene PHP ni Composer, y el CI (`.github/workflows/ci.yml`) solo se ejecuta en PR hacia `main`, así que los PR hacia `plan/mejoras` no se verifican. Además hay una vista muerta (`resources/views/compra/step2_old.blade.php`) y dos sistemas de Tailwind mezclados en `package.json` (Tailwind 3 con PostCSS, que es el que funciona, y `@tailwindcss/vite` 4, que no se usa).

## Tareas

1. CI: en `.github/workflows/ci.yml` cambia el disparador a
   ```yaml
   on:
     push:
       branches: [main, plan/mejoras]
     pull_request:
   ```
   y sube Node a `'22'`. Añade al paso «Run tests» `--display-skipped` (`php artisan test --display-skipped`).
2. PHP local sin `sudo`. Prueba en este orden y quédate con el primero que funcione:
   a) binario estático de static-php-cli:
   ```bash
   mkdir -p ~/.local/php && cd ~/.local/php
   curl -fsSL -o php.tar.gz https://dl.static-php.dev/static-php-cli/common/php-8.4-cli-linux-x86_64.tar.gz
   tar xzf php.tar.gz && rm php.tar.gz && ./php -v && ./php -m | grep -iE "pdo_sqlite|mbstring|intl|gd|bcmath|zip|curl|dom"
   ```
   Si faltan extensiones (`pdo_sqlite`, `mbstring`, `dom`, `curl`, `zip`, `bcmath` son imprescindibles), prueba la variante `bulk` (`php-8.4-cli-linux-x86_64.tar.gz` en `https://dl.static-php.dev/static-php-cli/bulk/`).
   b) `micromamba` (sin `sudo`): `curl -Ls https://micro.mamba.pm/api/micromamba/linux-64/latest | tar -xvj -C ~/.local bin/micromamba && ~/.local/bin/micromamba create -y -p ~/.local/php-env -c conda-forge php=8.4`.
   Después, Composer: `cd ~/.local/php && curl -fsSL -o composer.phar https://getcomposer.org/download/latest-stable/composer.phar`.
3. Si lo consigues, añade a `plan/REGLAS.md`, en «Entorno de esta máquina», las líneas exactas para usarlo, por ejemplo: `export PATH=$HOME/.local/php:$PATH` y `alias composer="php $HOME/.local/php/composer.phar"`, y quita la frase «La sesión 00 deja…». Si no lo consigues, escríbelo así en `plan/REGLAS.md` («Sin PHP local: la verificación de PHP es solo el CI; las capturas se hacen contra la demo pública, sin comprar») y deja un aviso en `plan/AVISOS.md` recomendando a Ayman `sudo apt install php8.4-cli php8.4-sqlite3 php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath php8.4-gd php8.4-intl composer` (u ondrej/php si su Ubuntu no trae 8.4). No es bloqueante: sigue con el resto.
4. Con PHP local: `composer install`, `cp -n .env.example .env`, `php artisan key:generate`, `touch database/database.sqlite`, `php artisan migrate:fresh --seed`, `npm ci`, `npm run build`, `php artisan test --display-skipped`. Anota en el PR cuántas pruebas pasan, fallan o se saltan **antes de tocar nada** (línea base). Si alguna falla ya, no la arregles: anótala.
5. Borra `resources/views/compra/step2_old.blade.php` tras comprobar que nadie la usa (`grep -rn "step2_old" app resources routes` vacío).
6. Quita `@tailwindcss/vite` de `devDependencies` (no se importa en `vite.config.js`; compruébalo con `grep -rn "tailwindcss/vite" vite.config.js resources`) y ejecuta `npm install` para actualizar `package-lock.json`. El proyecto sigue con Tailwind 3 y PostCSS: es lo que ya funciona y migrar a 4 no aporta nada a este plan.
7. Corrige el README: «Live demo: _(pending deployment…)_» → `Live demo: https://cineflow-ufe0.onrender.com/` (la demo existe).

## No hacer

- No cambies estilos, vistas (salvo borrar `step2_old`) ni lógica.
- No actualices versiones de Laravel ni de paquetes de PHP.

## Criterios de aceptación

- El PR dispara el job `tests` del CI y está en verde.
- `plan/REGLAS.md` dice exactamente cómo usar PHP en esta máquina (o que no hay).
- Línea base de pruebas anotada en el PR.

## Verificación

Ver `plan/REGLAS.md`. Además `npm run build` tras quitar `@tailwindcss/vite`.

## Cierre

Commit(s) «Ejecuta el CI en cada PR», «Borra la vista antigua del paso 2», etc.; push; PR hacia `plan/mejoras`; fusión si está en verde; marca 00 como hecha en `plan/PLAN.md`.
