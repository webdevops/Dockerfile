#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:8.2-alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:8.2-alpine

COPY conf/ /opt/docker/

RUN set -x \
    # Install development environment
    && bash -c "$(curl -L https://installer.blackfire.io/installer.sh)" \
    && blackfire php:install \
    && apk-install \
        linux-headers \
        make \
        autoconf \
        g++ \
    && pecl install xdebug \
    && apk del -f --purge \
        autoconf \
        linux-headers \
        g++ \
        make \
    && docker-php-ext-enable xdebug \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap
