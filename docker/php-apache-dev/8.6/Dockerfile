#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-apache-dev:8.6
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-apache:8.6

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm \
    WEB_PHP_TIMEOUT=600 \
    WEB_PHP_SOCKET=""
ENV WEB_PHP_SOCKET=127.0.0.1:9000
ENV WEB_NO_CACHE_PATTERN="\.(css|js|gif|png|jpg|svg|json|xml)$"

COPY conf/ /opt/docker/

RUN --mount=type=bind,from=ghcr.io/php/pie:bin,source=/pie,target=/usr/local/bin/pie \
    set -x \
    # Install development environment
    && BLACKFIRE_INSTALL_METHOD=raw bash -c "$(curl -L https://installer.blackfire.io/installer.sh)" \
    && blackfire php:install \
    # TODO PHP 8.6: Tideways is not installed yet, tideways-php 5.47.0 crashes
    # PHP 8.6.0RC4 (ZTS) on startup
    && PIE_WORKING_DIRECTORY=/tmp/pie pie install --no-interaction --no-cache \
        --skip-enable-extension --make-parallel-jobs="$(nproc)" "xdebug/xdebug:^3.6@alpha" \
    && rm -rf /tmp/pie \
    && docker-php-ext-enable xdebug \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap \
    && docker-image-cleanup
