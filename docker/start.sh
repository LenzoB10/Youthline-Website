#!/bin/sh
# Starts the Youthline demo container (Render).
# Every start builds a fresh demo database with the sample data, because Render's free plan
# has no permanent disk: anything entered is reset on restart or redeploy.
set -e
cd /var/www/html

# Address of the site: Render provides it as RENDER_EXTERNAL_URL.
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-http://localhost}}"

# Encryption key: use the one from the hosting settings, or make one for this run.
if [ -z "$APP_KEY" ]; then
    export APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
fi

# Fresh SQLite database, folders and the public link for uploaded images.
mkdir -p database storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
touch database/database.sqlite
php artisan migrate:fresh --force --seed
php artisan storage:link --force || true

# Cache settings, routes and pages for speed (after seeding, which reads the environment).
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache database

# Render tells the app which port to listen on.
PORT="${PORT:-10000}"
sed -ri "s/Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
