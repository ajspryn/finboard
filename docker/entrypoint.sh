#!/bin/sh
set -e

echo "==> Starting FinBoard deployment setup..."

# Ensure storage & cache directories exist and are writable
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/framework/{cache,sessions,views}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    echo "==> Generating application key..."
    php artisan key:generate --force
fi

# Wait for database to be ready
echo "==> Waiting for database connection..."
max_tries=30
counter=0
until php -r "
    \$pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    echo 'connected';
" 2>/dev/null; do
    counter=$((counter + 1))
    if [ $counter -ge $max_tries ]; then
        echo "ERROR: Could not connect to database after ${max_tries} attempts."
        exit 1
    fi
    echo "  Database not ready yet, retrying in 3s... (attempt ${counter}/${max_tries})"
    sleep 3
done
echo "==> Database connected."

# Run migrations
echo "==> Running migrations..."
php artisan migrate --force

# Clear old cache and rebuild
echo "==> Caching configuration..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Create storage symlink
echo "==> Creating storage symlink..."
php artisan storage:link --force 2>/dev/null || true

echo "==> Setup complete. Starting services..."

# Start supervisor (manages nginx, php-fpm, queue workers)
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
