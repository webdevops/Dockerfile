#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:8.1-alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:8.1-alpine

COPY conf/ /opt/docker/

RUN set -x \
    # Install development environment
    && apk-install \
        make \
        autoconf \
        g++ \
    && pecl install xdebug \
    && apk del -f --purge \
        autoconf \
        g++ \
        make \
    && docker-php-ext-enable xdebug \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap \
    && docker-image-cleanup
