FROM php:7.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libonig-dev \
        unzip \
        openssl \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql mbstring \
    && docker-php-ext-enable opcache \
    && a2enmod rewrite

# Point Apache document root at Symfony's web/ directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/web
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-scripts --no-dev --optimize-autoloader

RUN mkdir -p var/cache var/logs var/sessions var/jwt \
    && chmod -R 777 var/

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
CMD ["/entrypoint.sh"]
