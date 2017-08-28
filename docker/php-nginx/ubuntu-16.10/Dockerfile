#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-nginx:ubuntu-16.10
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:ubuntu-16.10

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm
ENV WEB_PHP_SOCKET=127.0.0.1:9000
ENV WEB_PHP_TIMEOUT=600

COPY conf/ /opt/docker/

# Install nginx
RUN /usr/local/bin/apt-install \
        nginx \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 80 443
