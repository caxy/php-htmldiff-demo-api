#!/bin/sh
set -e

export APP_ENV=prod
export APP_DEBUG=0
export APP_SECRET="${APP_SECRET:-$(openssl rand -hex 32)}"

cd /var/www/html
php bin/console cache:clear --no-warmup 2>/dev/null || rm -rf var/cache/prod

exec apache2-foreground
