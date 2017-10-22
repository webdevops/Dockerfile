#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:alpine-php5
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:alpine-php5

COPY conf/ /opt/docker/

# Install development environment
RUN apk-install \
        # Install tools
        graphviz \
        # Tools
        nano \
        vim \
    && apk-install gcc php5-dev autoconf --virtual .pecl-deps \
    && pecl install xdebug \
    && apk del .pecl-deps \
    # Enable php development services
    && docker-service-enable syslog ssh postfix \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup
