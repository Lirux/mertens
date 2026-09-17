# syntax=docker/dockerfile:1
FROM php:8.3-apache-bookworm AS php-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl libicu-dev libonig-dev libssl-dev unzip \
    && docker-php-ext-install -j"$(nproc)" intl mbstring opcache \
    && pecl install mongodb-2.3.3 \
    && docker-php-ext-enable mongodb \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

FROM php-base AS build
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY . .
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --no-dev --optimize --no-interaction \
    && npm ci \
    && npm run build \
    && rm -f bootstrap/cache/*.php

FROM php-base AS runtime
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr INERTIA_SSR_ENABLED=false
COPY --from=build /var/www/html/app ./app
COPY --from=build /var/www/html/bootstrap ./bootstrap
COPY --from=build /var/www/html/config ./config
COPY --from=build /var/www/html/database ./database
COPY --from=build /var/www/html/public ./public
COPY --from=build /var/www/html/resources/views ./resources/views
COPY --from=build /var/www/html/routes ./routes
COPY --from=build /var/www/html/vendor ./vendor
COPY --from=build /var/www/html/artisan /var/www/html/composer.json ./
COPY docker-apache.conf /etc/apache2/sites-available/000-default.conf
COPY --chmod=755 docker-entrypoint.sh /usr/local/bin/mertens-entrypoint
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

HEALTHCHECK --interval=10s --timeout=5s --start-period=60s --retries=6 \
    CMD curl --fail --silent http://127.0.0.1/up > /dev/null || exit 1
ENTRYPOINT ["mertens-entrypoint"]
CMD ["apache2-foreground"]
