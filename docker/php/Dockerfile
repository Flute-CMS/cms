FROM php:7.4-fpm

RUN apt-get update && apt-get install -y libicu-dev libzip-dev \
    libicu-dev zlib1g-dev libpng-dev libjpeg-dev libxml2-dev \
    libfreetype6-dev libmcrypt-dev libpng-dev libcurl3-dev libonig-dev

# Install MySQL PDO
RUN docker-php-ext-install pdo pdo_mysql

RUN apt-get clean

RUN docker-php-ext-install intl opcache bcmath

WORKDIR /usr/share/nginx/html
