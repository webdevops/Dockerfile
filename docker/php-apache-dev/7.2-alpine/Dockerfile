#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-apache-dev:7.2-alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-apache:7.2-alpine

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm \
    WEB_PHP_TIMEOUT=600 \
    WEB_PHP_SOCKET=""
ENV WEB_PHP_SOCKET=127.0.0.1:9000
ENV WEB_NO_CACHE_PATTERN="\.(css|js|gif|png|jpg|svg|json|xml)$"

COPY conf/ /opt/docker/

RUN set -x \
    # Install development environment
    && wget -q -O blackfire-agent https://packages.blackfire.io/binaries/blackfire-agent/1.34.0/blackfire-agent-linux_static_amd64 \
    && mv blackfire-agent /usr/local/bin/ \
    && chmod +x /usr/local/bin/blackfire-agent \
    && wget -q -O blackfire.so https://packages.blackfire.io/binaries/blackfire-php/1.33.0/blackfire-php-alpine_amd64-php-72.so \
    && mv blackfire.so "$(php -r "echo ini_get('extension_dir');")/blackfire.so" \
    && mkdir /var/run/blackfire/ \
    && apk-install \
        make \
        autoconf \
        g++ \
    && pecl install xdebug \
    && apk del -f --purge \
        autoconf \
        g++ \
        make \
    && docker-php-ext-enable xdebug \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap \
    && docker-image-cleanup
