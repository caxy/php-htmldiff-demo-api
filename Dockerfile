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

RUN rm -f /etc/nginx/sites-enabled/default && \
    printf 'server {\n\
    listen 80 default_server;\n\
    server_name _;\n\
    root /var/www/html/web;\n\
    location / { try_files $uri /app.php$is_args$args; }\n\
    location ~ ^/app\\.php(/|$) {\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_split_path_info ^(.+\\.php)(/.*)$;\n\
        include fastcgi_params;\n\
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;\n\
        fastcgi_param DOCUMENT_ROOT $realpath_root;\n\
        internal;\n\
    }\n\
    location ~ \\.php$ { return 404; }\n\
}\n' > /etc/nginx/conf.d/app.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
CMD ["/entrypoint.sh"]
