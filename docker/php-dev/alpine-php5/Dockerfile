#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:alpine-php5
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:alpine-php5

COPY conf/ /opt/docker/

RUN set -x \
    # Install development environment
    && apk-install \
        # Install tools
        graphviz \
        # Tools
        nano \
        vim \
    && apk-install gcc php5-dev autoconf --virtual .pecl-deps \
    && pecl install xdebug-2.5.5 \
    && apk del .pecl-deps \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap \
    && docker-image-cleanup
