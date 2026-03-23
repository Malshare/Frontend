FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mysqli

RUN pear install Mail Net_SMTP

RUN a2enmod rewrite

RUN echo '<Directory /var/www/html/>\n\tAllowOverride All\n\tRequire all granted\n</Directory>' \
    > /etc/apache2/conf-enabled/override.conf

RUN echo "upload_max_filesize = 27M\npost_max_size = 28M" \
    > /usr/local/etc/php/conf.d/uploads.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader

COPY html/ html/

EXPOSE 80
