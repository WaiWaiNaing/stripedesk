FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock* ./
COPY application ./application
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

FROM php:7.3-apache

RUN a2enmod rewrite headers \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

COPY application ./application
COPY database ./database
COPY public ./public
COPY composer.json ./

COPY --from=vendor /app/vendor ./vendor

RUN chown -R www-data:www-data /var/www/html
