FROM php:8.2-cli

RUN apt-get update -y && apt-get install -y zip unzip mariadb-client

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions @composer gmp openssl mcrypt

RUN mkdir -p /app

COPY composer.json /tmp/composer/composer.json
COPY composer.lock /tmp/composer/composer.lock

RUN cd /tmp/composer && composer install --no-ansi --no-interaction --no-progress --no-scripts --no-dev && \
    composer clear-cache && cp -a /tmp/composer/vendor /app && rm -rf /tmp/composer

WORKDIR /app

COPY . .

# Rebuild autoloader to fix /tmp/composer classmap
RUN composer dump-autoload --no-ansi --no-interaction --optimize --no-scripts --classmap-authoritative

# Add php configuration
COPY php.ini /usr/local/etc/php/conf.d/php_app.ini

RUN chmod +x entrypoint.sh

ENTRYPOINT [ "/app/entrypoint.sh" ]
