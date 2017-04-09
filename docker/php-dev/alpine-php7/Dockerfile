#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:alpine-php7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:alpine-php7

COPY conf/ /opt/docker/

# Install development environment
RUN /usr/local/bin/apk-install \
        # Install tools
        graphviz \
        # Install php development stuff
        php7-xdebug \
    # Enable php development services
    && docker-service-enable ssh \
    && docker-service-enable postfix \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-dev --role webdevops-php-dev \
    && /opt/docker/bin/bootstrap.sh \
    && /usr/local/bin/docker-image-cleanup
