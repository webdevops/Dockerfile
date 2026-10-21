#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:8.3-alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:8.3-alpine

COPY conf/ /opt/docker/

RUN --mount=type=bind,from=ghcr.io/php/pie:bin,source=/pie,target=/usr/local/bin/pie \
    set -x \
    # Install development environment
    && bash -c "$(curl -L https://installer.blackfire.io/installer.sh)" \
    && blackfire php:install \
    && apk-install \
        linux-headers \
        make \
        autoconf \
        g++ \
        libtool \
        pkgconf \
    && PIE_WORKING_DIRECTORY=/tmp/pie pie install --no-interaction --no-cache \
        --skip-enable-extension --make-parallel-jobs="$(nproc)" xdebug/xdebug \
    && rm -rf /tmp/pie \
    && apk del -f --purge \
        autoconf \
        linux-headers \
        g++ \
        make \
        libtool \
        pkgconf \
    && docker-php-ext-enable xdebug \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap
