#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-nginx-dev:alpine-php7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-nginx:alpine-php7

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm
ENV WEB_PHP_SOCKET=127.0.0.1:9000
ENV WEB_PHP_TIMEOUT=600
ENV WEB_NO_CACHE_PATTERN="\.(css|js|gif|png|jpg|svg|json|xml)$"

COPY conf/ /opt/docker/

# Install development environment
RUN /usr/local/bin/apk-install \
        # Install tools
        graphviz \
        # Install php development stuff
        php7-xdebug \
    # Enable php development services
    && docker-service-enable syslog ssh postfix \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup
