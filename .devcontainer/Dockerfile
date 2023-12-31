FROM php:8.3-cli

RUN apt-get update -y && apt-get install -y mariadb-client git gnupg2 zip unzip

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions @composer apcu bcmath gmp intl mcrypt pcntl pcov pdo_mysql uuid xdebug

# Install Symfony CLI
COPY --from=ghcr.io/symfony-cli/symfony-cli:latest /usr/local/bin/symfony /usr/bin/symfony

COPY php.ini /usr/local/etc/php/conf.d/devcontainer.ini

WORKDIR /db-dumper

CMD ["/bin/sh", "-c", "while sleep 1000; do :; done"]

ARG USERNAME=vscode
ARG USER_UID=1000
ARG USER_GID=$USER_UID

RUN groupadd --gid $USER_GID $USERNAME \
    && useradd -s /bin/bash --uid $USER_UID --gid $USER_GID -m $USERNAME

USER $USERNAME
