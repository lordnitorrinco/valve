# PHP-FPM backend container for the admission API.
# Based on Alpine for minimal image size (~50MB).

FROM php:8.3-fpm-alpine

# Install system dependencies and PHP extensions:
#  - pdo_mysql: database connectivity
#  - curl: webhook forwarding to external endpoints
RUN apk add --no-cache libcurl curl-dev \
    && docker-php-ext-install pdo pdo_mysql curl

# Disable PHP version exposure in response headers
RUN echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini

# Create upload directory owned by the www-data user
# (stored outside the web root for security)
RUN mkdir -p /var/www/uploads \
    && chown -R www-data:www-data /var/www/uploads

# Copy application files
COPY config/       /var/www/config/
COPY app/           /var/www/app/
COPY public/index.php /var/www/html/index.php

WORKDIR /var/www/html

EXPOSE 9000
