#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:8.1
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:8.1

COPY conf/ /opt/docker/

RUN set -x \
    # Install development environment
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap \
    && docker-image-cleanup
