FROM php:8.1-fpm

# Устанавливаем зависимости
RUN apt-get update && apt-get install -y libzip-dev libgmp-dev \
    libicu-dev zlib1g-dev libpng-dev libjpeg-dev libxml2-dev \
    libfreetype6-dev libonig-dev libwebp-dev libjpeg62-turbo-dev \
    libxpm-dev libcurl4-openssl-dev pkg-config

# Устанавливаем расширения PHP
RUN docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql mbstring ctype curl
RUN docker-php-ext-install zip
RUN docker-php-ext-install gmp
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype
RUN docker-php-ext-install -j$(nproc) gd
RUN docker-php-ext-install -j$(nproc) intl bcmath
RUN apt-get clean

# Hide PHP version
RUN echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini

# Устанавливаем рабочий каталог
WORKDIR /var/www/html/

# Установка Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Копируем файлы приложения
COPY . .

RUN chmod -R 775 /var/www/html/storage
CMD bash -c "composer install --ignore-platform-reqs && php-fpm"
