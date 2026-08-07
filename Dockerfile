# syntax=docker/dockerfile:1

# ---- Stage 1: build frontend assets ----
# `npm run build` runs the Wayfinder Vite plugin, which shells out to
# `php artisan wayfinder:generate` to introspect routes. That means this
# stage needs PHP + the Composer dependencies available, not just Node.
FROM php:8.4-cli-alpine AS frontend

WORKDIR /var/www/html

RUN apk add --no-cache nodejs npm icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" bcmath intl mbstring zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --optimize-autoloader

COPY . .

RUN npm ci && npm run build

# ---- Stage 2: PHP-FPM application image ----
FROM php:8.4-fpm-alpine AS app

WORKDIR /var/www/html

RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev postgresql-dev \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath intl mbstring opcache pcntl pdo_mysql pdo_pgsql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --optimize-autoloader

COPY . .
COPY --from=frontend /var/www/html/public/build public/build

RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 9000

HEALTHCHECK --interval=5s --timeout=3s --start-period=10s --retries=5 \
    CMD php -r '$c=@fsockopen("127.0.0.1",9000,$e,$s,1);if(!$c){exit(1);}fclose($c);'

CMD ["php-fpm"]
