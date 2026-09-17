#!/bin/sh
set -eu

cd /var/www/html

if [ "$1" = 'apache2-foreground' ]; then
    mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

    if [ -z "${APP_KEY:-}" ]; then
        if [ ! -s storage/app/private/docker-app-key ]; then
            (umask 077; php -r 'echo "base64:".base64_encode(random_bytes(32));' > storage/app/private/docker-app-key)
        fi
        APP_KEY="$(cat storage/app/private/docker-app-key)"
        export APP_KEY
    fi

    chown -R www-data:www-data storage bootstrap/cache
    runuser -u www-data -- php artisan config:clear --no-interaction
    runuser -u www-data -- php artisan config:cache --no-interaction
    runuser -u www-data -- php artisan migrate --force --no-interaction
    runuser -u www-data -- php artisan view:clear --no-interaction

    if [ "${DEMO_SEED:-false}" = 'true' ] && [ ! -f storage/app/private/docker-demo-seeded ]; then
        runuser -u www-data -- php artisan db:seed --force --no-interaction
        touch storage/app/private/docker-demo-seeded
    fi
fi

exec docker-php-entrypoint "$@"
