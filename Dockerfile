# ─── Build stage: Node (frontend assets) ──────────────────────────────────────
FROM node:22-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources

RUN npm run build

# ─── Build stage: Composer (PHP dependencies) ─────────────────────────────────
FROM composer:2 AS composer-builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN mkdir -p database && touch database/database.sqlite \
    && composer dump-autoload --optimize --no-dev --no-scripts

# ─── Production image ─────────────────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS production

ARG BUILD_VERSION=dev
ARG GITHUB_REPOSITORY=""
ENV APP_VERSION=${BUILD_VERSION}

LABEL org.opencontainers.image.title="Laravel Reverb Server"
LABEL org.opencontainers.image.description="Self-hosted WebSocket server powered by Laravel Reverb"
LABEL org.opencontainers.image.version="${BUILD_VERSION}"
LABEL org.opencontainers.image.source="https://github.com/${GITHUB_REPOSITORY}"

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite-dev \
    curl \
    && docker-php-ext-install pdo_sqlite pcntl

# Install Redis extension (for scaling support)
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# PHP production config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

# Copy application
COPY --from=composer-builder /app /var/www/html
COPY --from=node-builder /app/public/build /var/www/html/public/build

# Create required directories & set permissions
RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        database \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Config files
COPY docker/nginx.conf       /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php-fpm.conf     /usr/local/etc/php-fpm.d/zz-docker.conf

EXPOSE 80 8080

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
