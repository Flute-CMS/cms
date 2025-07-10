FROM serversideup/php:8.3-fpm-nginx AS base

USER root
WORKDIR /var/www/html

COPY composer.json composer.lock /tmp/

RUN install-php-extensions @composer bcmath gmp ioncube \
    && rm -rf /tmp/composer.json /tmp/composer.lock

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY nginx/default.conf /etc/nginx/conf.d/default.conf

USER www-data

# -----------------------------------------------------------------------------
# Builder stage: install PHP dependencies (vendor)
# -----------------------------------------------------------------------------
FROM base AS vendor

USER root
WORKDIR /app
RUN mkdir -p /app && chown -R www-data:www-data /app

USER www-data
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

# -----------------------------------------------------------------------------
# Final runtime image
# -----------------------------------------------------------------------------
FROM base AS runtime

COPY --chown=www-data:www-data . /var/www/html

COPY --from=vendor /app/vendor /var/www/html/vendor

RUN composer dump-autoload --optimize --apcu --no-dev --classmap-authoritative --no-interaction

RUN echo "OK" > /var/www/html/health

EXPOSE 80 443 9000

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 CMD [ "curl", "-f", "http://localhost/health" ] || exit 1

USER www-data