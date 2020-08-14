#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:8.0
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:8.0

COPY conf/ /opt/docker/

RUN set -x \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap \
    && docker-image-cleanup
