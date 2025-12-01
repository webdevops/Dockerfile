#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:8.5-alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:8.5-alpine

COPY conf/ /opt/docker/

RUN set -x \
    # Install development environment
    && bash -c "$(curl -L https://installer.blackfire.io/installer.sh)" \
    && blackfire php:install \
    && apk-install \
        linux-headers \
        make \
        autoconf \
        g++ \
    && wget https://github.com/php/pie/releases/download/1.3.1/pie.phar -O /usr/local/bin/pie \
    && chmod +x /usr/local/bin/pie \
    && pie install xdebug/xdebug:^3.5.0@alpha \
    && rm -f /usr/local/bin/pie \
    && apk del -f --purge \
        autoconf \
        linux-headers \
        g++ \
        make \
    && docker-php-ext-enable xdebug \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap
