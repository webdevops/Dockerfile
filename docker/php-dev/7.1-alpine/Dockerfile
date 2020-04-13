#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:7.1-alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:7.1-alpine

COPY conf/ /opt/docker/

RUN set -x \
    # Install development environment
    && wget -q -O blackfire-agent https://packages.blackfire.io/binaries/blackfire-agent/1.34.0/blackfire-agent-linux_static_amd64 \
    && mv blackfire-agent /usr/local/bin/ \
    && chmod +x /usr/local/bin/blackfire-agent \
    && wget -q -O blackfire.so https://packages.blackfire.io/binaries/blackfire-php/1.33.0/blackfire-php-alpine_amd64-php-71.so \
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
