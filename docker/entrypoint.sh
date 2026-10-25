#!/bin/sh
set -e

PORT=${PORT:-80}
echo "Configuring application on port $PORT..."

# Replace listen port in nginx.conf
sed -i "s/listen 80;/listen $PORT;/g" /etc/nginx/nginx.conf

# Map Render PostgreSQL DATABASE_URL to DB_URL if available
if [ -n "$DATABASE_URL" ] && [ -z "$DB_URL" ]; then
    echo "Using PostgreSQL DATABASE_URL..."
    export DB_URL="$DATABASE_URL"
    export DB_CONNECTION="pgsql"
fi

# Create required directories for Nginx and PHP
mkdir -p /run/nginx /var/run /var/log/nginx /var/lib/nginx/tmp
chown -R www-data:www-data /run/nginx /var/log/nginx /var/lib/nginx

# Ensure storage directories exist
mkdir -p /var/www/html/database \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

# If SQLite, ensure database.sqlite exists
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    echo "Ensuring SQLite database file..."
    if [ ! -f /var/www/html/database/database.sqlite ]; then
        touch /var/www/html/database/database.sqlite
    fi
    chown -R www-data:www-data /var/www/html/database
    chmod -R 775 /var/www/html/database
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink
php artisan storage:link --force || true

# Run migrations
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || true
fi

# Verify Nginx configuration
echo "Verifying Nginx configuration..."
nginx -t

echo "Starting Supervisord..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
