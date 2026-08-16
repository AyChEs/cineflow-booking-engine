#!/usr/bin/env sh
set -e

cd /var/www/html

# ── App key ──────────────────────────────────────────────────────────────────
if [ -z "${APP_KEY}" ] && ! grep -q "^APP_KEY=base64" .env 2>/dev/null; then
    php artisan key:generate --force
fi

# ── Base de datos ────────────────────────────────────────────────────────────
FRESH=0
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    case "$DB_FILE" in
        /*) : ;;                              # ruta absoluta, la respetamos
        *)  DB_FILE="/var/www/html/database/database.sqlite" ;;
    esac
    if [ ! -f "$DB_FILE" ]; then
        mkdir -p "$(dirname "$DB_FILE")"
        touch "$DB_FILE"
        FRESH=1
    fi
fi

# Migración (idempotente). Sembramos solo en el primer arranque.
php artisan migrate --force
if [ "$FRESH" = "1" ]; then
    php artisan db:seed --force
fi

# ── Cachés de producción ─────────────────────────────────────────────────────
php artisan storage:link 2>/dev/null || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# ── Servidor ─────────────────────────────────────────────────────────────────
PORT="${PORT:-8000}"
echo "▶ CineFlow arrancando en 0.0.0.0:${PORT}"
# --no-reload permite varios workers (PHP_CLI_SERVER_WORKERS) para demostrar la concurrencia
exec php artisan serve --host=0.0.0.0 --port="${PORT}" --no-reload
