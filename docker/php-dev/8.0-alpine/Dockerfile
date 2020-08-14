#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:8.0-alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:8.0-alpine

COPY conf/ /opt/docker/

RUN set -x \
    
    && apk-install \
        make \
        autoconf \
        g++ \
    && pickle install xdebug \
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
