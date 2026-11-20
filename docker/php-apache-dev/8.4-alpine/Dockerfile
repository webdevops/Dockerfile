#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-apache-dev:8.4-alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-apache:8.4-alpine

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm \
    WEB_PHP_TIMEOUT=600 \
    WEB_PHP_SOCKET=""
ENV WEB_PHP_SOCKET=127.0.0.1:9000
ENV WEB_NO_CACHE_PATTERN="\.(css|js|gif|png|jpg|svg|json|xml)$"

COPY conf/ /opt/docker/

RUN --mount=type=bind,from=ghcr.io/php/pie:bin,source=/pie,target=/usr/local/bin/pie \
    --mount=type=secret,id=COMPOSER_AUTH,env=COMPOSER_AUTH \
    set -x \
    # Install development environment
    && bash -c "$(curl -L https://installer.blackfire.io/installer.sh)" \
    # `blackfire php:install` installs the glibc probe on Alpine arm64 and with ZTS (PHP 8.6); musl cannot load it
    # (on arm64 since Blackfire 2026.11.3, which needs ld-linux-aarch64.so.1). Install the Alpine probe directly.
    && mkdir -p /tmp/blackfire \
    && curl -fsSL --proto '=https' --proto-redir '=https' --retry 3 "https://blackfire.io/api/v1/releases/probe/php/alpine/$(uname -m | sed 's/x86_64/amd64/;s/aarch64/arm64/')/$(php -r 'echo PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_ZTS ? "-zts" : "";')" | tar xz -C /tmp/blackfire \
    && mv /tmp/blackfire/blackfire-*.so "$(php -r 'echo ini_get("extension_dir");')/blackfire.so" \
    && printf '[blackfire]\nextension=blackfire.so\nblackfire.log_level=1\n' > /usr/local/etc/php/conf.d/99-blackfire.ini \
    && rm -rf /tmp/blackfire \
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
