FROM php:8.4-fpm-alpine

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN install-php-extensions pdo_pgsql redis intl gd zip pcntl bcmath opcache

# Match the host user's UID/GID so files created in the bind mount stay editable on the host.
ARG UID=1000
ARG GID=1000
RUN apk add --no-cache shadow \
    && groupmod -o -g "${GID}" www-data \
    && usermod -o -u "${UID}" -g "${GID}" www-data \
    && apk del shadow \
    && mkdir -p /home/www-data/.composer \
    && chown -R www-data:www-data /var/www /home/www-data

ENV COMPOSER_HOME=/home/www-data/.composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/entrypoint

WORKDIR /var/www/html
USER www-data

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
