#!/bin/sh
set -e

APP_DIR="/var/www/html"

# ── Validate .env ─────────────────────────────────────────────────────────────
if [ ! -f "${APP_DIR}/.env" ]; then
    echo "ERROR: .env file not found."
    echo "Download it first: curl -o .env https://raw.githubusercontent.com/adityadarma/laravel-reverb-server/main/.env.sqlite.example"
    exit 1
fi

# ── Validate APP_KEY ──────────────────────────────────────────────────────────
APP_KEY_VALUE=$(grep -E '^APP_KEY=' "${APP_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")

if [ -z "$APP_KEY_VALUE" ]; then
    echo "ERROR: APP_KEY is not set in .env"
    echo "Generate one with: php -r \"echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;\""
    exit 1
fi

# ── SQLite: create database file if not exists ────────────────────────────────
if [ ! -f "${APP_DIR}/database/database.sqlite" ]; then
    touch "${APP_DIR}/database/database.sqlite"
fi

# ── Run migrations ────────────────────────────────────────────────────────────
php artisan migrate --force

# ── Cache config, routes, views ───────────────────────────────────────────────
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
