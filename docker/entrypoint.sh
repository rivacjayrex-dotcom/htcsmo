#!/bin/sh
set -eu

port="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${port}/" /etc/apache2/ports.conf
sed -i "s/*:10000/*:${port}/" /etc/apache2/sites-available/000-default.conf

cd /var/www/html
php artisan storage:link || true
php artisan config:cache
php artisan migrate --force

exec apache2-foreground
