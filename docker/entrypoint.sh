#!/bin/sh
set -e

# Каталоги фреймворка поверх volume (Laravel требует их на запись).
# Конфиг передаётся через переменные окружения compose (env_file), файл .env не используется.
mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs storage/app/public bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# public/ на общем volume: наполняем из шаблона образа (статика Vite + index.php)
mkdir -p /var/www/public
cp -a /opt/public-template/. /var/www/public/
chown -R www-data:www-data /var/www/public

# Первый запуск: ключ, миграции, сиды
php artisan migrate --force
php artisan db:seed --force

exec "$@"
