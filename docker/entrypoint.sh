#!/bin/sh
set -e

# Parse Railway MySQL URL (mysql://user:password@host:port/dbname)
_URL="${MYSQL_URL:-${DATABASE_URL:-}}"
if [ -n "$_URL" ]; then
    _USER=$(printf '%s' "$_URL" | sed 's|.*://\([^:]*\):.*|\1|')
    _PASS=$(printf '%s' "$_URL" | sed 's|.*://[^:]*:\([^@]*\)@.*|\1|')
    _HOST=$(printf '%s' "$_URL" | sed 's|.*@\([^:/]*\).*|\1|')
    _PORT=$(printf '%s' "$_URL" | sed 's|.*@[^:]*:\([0-9]*\)/.*|\1|')
    _NAME=$(printf '%s' "$_URL" | sed 's|.*/\([^?]*\).*|\1|')
fi

DB_HOST="${MYSQLHOST:-${_HOST:-127.0.0.1}}"
DB_PORT="${MYSQLPORT:-${_PORT:-3306}}"
DB_NAME="${MYSQLDATABASE:-${_NAME:-symfony}}"
DB_USER="${MYSQLUSER:-${_USER:-root}}"
DB_PASS="${MYSQLPASSWORD:-${_PASS:-}}"
APP_SECRET="${APP_SECRET:-$(openssl rand -hex 32)}"

cat > /var/www/html/app/config/parameters.yml <<YAML
parameters:
    database_host:     '${DB_HOST}'
    database_port:     '${DB_PORT}'
    database_name:     '${DB_NAME}'
    database_user:     '${DB_USER}'
    database_password: '${DB_PASS}'
    mailer_transport:  smtp
    mailer_host:       127.0.0.1
    mailer_user:       ~
    mailer_password:   ~
    secret:            '${APP_SECRET}'
    jwt_private_key_path: '%kernel.root_dir%/../var/jwt/private.pem'
    jwt_public_key_path:  '%kernel.root_dir%/../var/jwt/public.pem'
    jwt_key_pass_phrase:  ''
    jwt_token_ttl:        3600
YAML

if [ ! -f /var/www/html/var/jwt/private.pem ]; then
    openssl genrsa -out /var/www/html/var/jwt/private.pem 2048
    openssl rsa -pubout -in /var/www/html/var/jwt/private.pem -out /var/www/html/var/jwt/public.pem
fi

cd /var/www/html
# Clear cache without warmup (Symfony 3.2 + PHP 7.4 has ReflectionClass serialization issues in warmup)
php bin/console cache:clear --env=prod --no-debug --no-warmup 2>/dev/null || \
    rm -rf var/cache/prod

php-fpm -D

# Remove Debian's default site to prevent duplicate default_server conflict
rm -f /etc/nginx/sites-enabled/default

exec nginx -g 'daemon off;'
