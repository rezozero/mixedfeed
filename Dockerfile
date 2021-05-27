FROM roadiz/php80-nginx-alpine:latest
MAINTAINER Ambroise Maupate <ambroise@rezo-zero.com>
ENV USER_UID=1000
ARG USER_UID=1000

COPY --chown=www-data:www-data . /var/www/html/

RUN apk add --no-cache shadow \
    && curl -sS https://getcomposer.org/installer | \
       php -- --install-dir=/usr/bin/ --filename=composer \
    && composer install --no-plugins --no-scripts --prefer-dist \
    && composer dump-autoload --optimize --apcu \
    && usermod -u ${USER_UID} www-data \
    && groupmod -g ${USER_UID} www-data \
    && mkdir -p /var/www/html/tmp/client_body

ENTRYPOINT exec /usr/bin/supervisord -n -c /etc/supervisord.conf
