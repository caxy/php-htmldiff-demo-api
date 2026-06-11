#!/bin/sh
set -e

export APP_ENV=prod
export APP_DEBUG=0
export APP_SECRET="${APP_SECRET:-some_default_secret_change_in_production}"

# Fix MPM conflict if multiple MPMs are enabled (Railway build cache issue)
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# Railway injects PORT env var — make Apache listen on it
if [ -n "$PORT" ]; then
    sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
    sed -i "s/:80/:$PORT/" /etc/apache2/sites-enabled/000-default.conf
fi

# Symfony generates its cache lazily on first request
rm -rf /var/www/html/var/cache/prod

exec apache2-foreground
