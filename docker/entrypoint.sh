#!/bin/sh
set -e

export APP_ENV=prod
export APP_DEBUG=0
export APP_SECRET="${APP_SECRET:-some_default_secret_change_in_production}"

# Symfony generates its cache lazily on first request — no bin/console needed
rm -rf /var/www/html/var/cache/prod

exec apache2-foreground
