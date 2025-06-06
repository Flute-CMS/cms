FROM serversideup/php:8.3-fpm-nginx

USER root
WORKDIR /var/www/html

# Install PHP extensions
RUN install-php-extensions bcmath gmp intl gd ioncube

# Install IonCube Loader
RUN cd /tmp && \
    curl -fSL https://downloads.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz -o ioncube.tar.gz && \
    tar -xvzf ioncube.tar.gz && \
    PHP_EXT_DIR=$(php -i | grep "^extension_dir" | awk '{print $3}') && \
    cp /tmp/ioncube/ioncube_loader_lin_8.3.so $PHP_EXT_DIR/ && \
    echo "zend_extension=$PHP_EXT_DIR/ioncube_loader_lin_8.3.so" > /usr/local/etc/php/conf.d/00-ioncube.ini && \
    # Add IonCube encoded paths optimization
    echo "ioncube.loader.encoded_paths = \"/var/www/html/app/Modules\"" >> /usr/local/etc/php/conf.d/00-ioncube.ini && \
    rm -rf /tmp/ioncube.tar.gz /tmp/ioncube

# PHP config
ENV PHP_OPCACHE_ENABLE=1 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=256 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=65407 \
    PHP_OPCACHE_INTERNED_STRINGS_BUFFER=16 \
    PHP_OPCACHE_REVALIDATE_FREQ=0 \
    PHP_UPLOAD_MAX_FILE_SIZE=100M \
    PHP_POST_MAX_SIZE=100M

# Composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader && composer clear-cache

# App code
COPY . .

# Permissions
RUN chown -R www-data:www-data /var/www/html

USER www-data