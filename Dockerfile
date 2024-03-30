FROM php:7.4-fpm

# Устанавливаем зависимости
RUN apt-get update && apt-get install -y libzip-dev libgmp-dev \
    libicu-dev zlib1g-dev libpng-dev libjpeg-dev libxml2-dev \
    libfreetype6-dev libmcrypt-dev libcurl3-dev libonig-dev \
    libwebp-dev libjpeg62-turbo-dev libxpm-dev

# Устанавливаем расширения PHP
RUN docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql mbstring json ctype curl
RUN docker-php-ext-install zip
RUN docker-php-ext-install gmp
RUN pecl install mcrypt-1.0.7
RUN docker-php-ext-enable mcrypt
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype
RUN docker-php-ext-install -j$(nproc) gd
RUN docker-php-ext-install -j$(nproc) intl bcmath
RUN apt-get clean

# Устанавливаем рабочий каталог
WORKDIR /var/www/html/

# Установка Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Копируем файлы приложения
COPY . .

RUN chmod -R 775 /var/www/html/storage
CMD bash -c "composer install && php-fpm"