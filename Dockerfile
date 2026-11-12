# ---- Stage 1: сборка фронтенда (Vue 3 + Vite) ----
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---- Stage 2: PHP-зависимости ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction --no-progress
COPY . .
RUN composer dump-autoload --optimize

# ---- Stage 3: рантайм php-fpm ----
FROM php:8.4-fpm-alpine
RUN apk add --no-cache sqlite-dev && docker-php-ext-install pdo_sqlite
WORKDIR /var/www
COPY --from=vendor /app/vendor ./vendor
COPY --from=vendor /app/artisan ./artisan
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY public ./public
COPY resources ./resources
COPY routes ./routes
COPY storage ./storage
COPY .env.example ./.env.example
# Собранный фронтенд поверх public/
COPY --from=frontend /app/public/build ./public/build
RUN chown -R www-data:www-data storage bootstrap/cache \
    && php artisan storage:link 2>/dev/null || true \
    && cp -a /var/www/public /opt/public-template
ENV APP_ENV=production APP_DEBUG=false
