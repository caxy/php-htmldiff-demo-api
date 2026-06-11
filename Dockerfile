FROM php:7.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        libonig-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql mbstring \
    && docker-php-ext-enable opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Install PHP deps without running Symfony scripts (parameters.yml is generated at runtime)
RUN composer install --no-scripts --no-dev --optimize-autoloader

RUN mkdir -p var/cache var/logs var/sessions var/jwt \
    && chmod -R 777 var/

COPY docker/nginx.conf /etc/nginx/sites-enabled/default
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
CMD ["/entrypoint.sh"]
