#!/bin/sh
set -eu

port="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${port}/" /etc/apache2/ports.conf
sed -i "s/*:10000/*:${port}/" /etc/apache2/sites-available/000-default.conf

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY must be configured as a persistent environment variable."
    exit 1
fi

# Preserve file logging while exposing Laravel exceptions in container logs.
case "${LOG_CHANNEL:-stack}" in
    stack)
        case ",${LOG_STACK:-single}," in
            *,stderr,*) ;;
            *) export LOG_STACK="${LOG_STACK:-single},stderr" ;;
        esac
        ;;
    single|daily)
        export LOG_STACK="${LOG_CHANNEL},stderr"
        export LOG_CHANNEL=stack
        ;;
esac

php artisan storage:link || true
php artisan view:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

# Keep scheduled deadline notifications running in the web-service container.
# The scheduler uses a shared cache lock, so horizontally scaled instances do
# not execute the same daily notification job more than once.
php artisan schedule:work &
php artisan queue:work --queue=mail,default --sleep=5 --tries=3 --timeout=30 &

exec apache2-foreground
