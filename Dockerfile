# syntax=docker/dockerfile:1

# Stage 1: build front-end assets (Vite + Tailwind)
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
RUN npm run build

# Stage 2: PHP dependencies (Composer)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction --ignore-platform-reqs

# Stage 3: final runtime image
FROM php:8.4-cli AS app

# Extensiones de sistema y de PHP requeridas por Laravel + MySQL/SQLite
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libonig-dev libxml2-dev libicu-dev libjpeg-dev libfreetype6-dev libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_sqlite mbstring bcmath zip gd exif intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Código de la aplicación
COPY . .
# Dependencias y assets ya compilados desde las etapas anteriores
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# Permisos de escritura de Laravel
RUN chmod -R 775 storage bootstrap/cache \
    && cp -n .env.example .env || true

# El built-in server usa varios workers para poder demostrar la concurrencia
ENV PHP_CLI_SERVER_WORKERS=4
ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 8000
ENTRYPOINT ["docker/entrypoint.sh"]
