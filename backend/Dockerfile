FROM php:8.3-fpm-alpine

RUN apk add --no-cache libcurl curl-dev \
    && docker-php-ext-install pdo pdo_mysql curl

RUN echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini

RUN mkdir -p /var/www/uploads \
    && chown -R www-data:www-data /var/www/uploads

COPY config/       /var/www/config/
COPY app/           /var/www/app/
COPY public/index.php /var/www/html/index.php

WORKDIR /var/www/html

EXPOSE 9000
