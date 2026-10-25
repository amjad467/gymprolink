#!/bin/sh
set -e

# Dynamically set port for Render
PORT=${PORT:-80}
echo "Configuring Nginx port: $PORT"
sed -i "s/listen 80;/listen $PORT;/g" /etc/nginx/http.d/default.conf

# Support Render PostgreSQL DATABASE_URL automatically
if [ -n "$DATABASE_URL" ] && [ -z "$DB_URL" ]; then
    echo "Found DATABASE_URL, mapping to DB_URL for pgsql connection..."
    export DB_URL="$DATABASE_URL"
    export DB_CONNECTION="pgsql"
fi

# If using SQLite, ensure database file exists
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    echo "Using SQLite database..."
    mkdir -p /var/www/html/database
    if [ ! -f /var/www/html/database/database.sqlite ]; then
        touch /var/www/html/database/database.sqlite
    fi
    chown -R www-data:www-data /var/www/html/database
    chmod -R 775 /var/www/html/database
fi

# Ensure storage directories and permissions
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink
php artisan storage:link --force || true

# Discover packages
php artisan package:discover --ansi || true

# Production caches
if [ "$APP_ENV" = "production" ]; then
    echo "Caching configuration, routes, and views..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Run migrations
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || true
fi

echo "Starting Supervisor (PHP-FPM + Nginx)..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
