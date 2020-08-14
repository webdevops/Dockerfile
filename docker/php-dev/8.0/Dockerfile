#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:8.0
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:8.0

COPY conf/ /opt/docker/

RUN set -x \
    
    && pickle install xdebug \
    && docker-php-ext-enable xdebug \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap \
    && docker-image-cleanup
