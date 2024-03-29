FROM php:7.4-fpm

# Устанавливаем зависимости
RUN apt-get update && apt-get install -y \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libxpm-dev \
    libfreetype6-dev \
    zlib1g-dev \
    libzip-dev \
    default-mysql-client

# Устанавливаем расширения PHP
RUN docker-php-ext-install pdo_mysql zip
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype
RUN docker-php-ext-install gd

# Установка Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Копируем файлы приложения
COPY . /var/www/html/

# Устанавливаем рабочий каталог
WORKDIR /var/www/html/

# Set permissions for storage
RUN chmod -R ugo+rw /var/www/html/storage