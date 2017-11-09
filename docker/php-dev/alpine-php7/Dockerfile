#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:alpine-php7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:alpine-php7

COPY conf/ /opt/docker/

RUN set -x \
    # Install development environment
    && apk-install \
        # Install tools
        graphviz \
        # Install php development stuff
        php7-xdebug \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap \
    && docker-image-cleanup
