FROM serversideup/php:8.3-fpm-nginx

USER root

RUN install-php-extensions bcmath gmp ioncube

COPY . /var/www/html

USER www-data