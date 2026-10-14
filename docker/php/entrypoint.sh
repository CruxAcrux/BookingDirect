#!/bin/sh
set -e

# First-run bootstrap so `docker compose up` works on a fresh clone.
if [ -f composer.json ] && [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -f artisan ] && [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --ansi
fi

exec "$@"
