#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-apache-dev:8.5
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-apache:8.5

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
    && BLACKFIRE_INSTALL_METHOD=raw bash -c "$(curl -L https://installer.blackfire.io/installer.sh)" \
    && blackfire php:install \
    && echo 'deb https://packages.tideways.com/apt-packages-main any-version main' | tee /etc/apt/sources.list.d/tideways.list \
    && wget -qO - https://packages.tideways.com/key.gpg | apt-key add - \
    && apt-install tideways-php tideways-daemon \
    && wget https://github.com/php/pie/releases/download/1.3.1/pie.phar -O /usr/local/bin/pie \
    && chmod +x /usr/local/bin/pie \
    && pie install xdebug/xdebug:^3.5.0@alpha \
    && rm -f /usr/local/bin/pie \
    && docker-php-ext-enable xdebug \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap \
    && docker-image-cleanup
