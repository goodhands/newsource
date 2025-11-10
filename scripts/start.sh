#!/usr/bin/env bash
echo "Running composer"
composer install --no-dev --working-dir=/var/www/html

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate --force

echo "Seeding database"
php artisan db:seed --force

echo "Fetching initial articles"
php artisan articles:fetch

# Start scheduler & queue worker
echo "Starting scheduler and queue worker..."
nohup php artisan schedule:work > /dev/null 2>&1 &
nohup php artisan queue:work --tries=3 > /dev/null 2>&1 &

# Keep container running (Nginx/PHP-FPM)
exec "$@"
