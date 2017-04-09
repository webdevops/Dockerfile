#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-nginx-dev:alpine-php7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-dev:alpine-php7

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm
ENV WEB_PHP_SOCKET=127.0.0.1:9000
ENV WEB_NO_CACHE_PATTERN="\.(css|js|gif|png|jpg|svg|json|xml)$"

COPY conf/ /opt/docker/

# Install nginx
RUN /usr/local/bin/apk-install \
        nginx \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-nginx --role webdevops-php-nginx --role webdevops-nginx-dev --role webdevops-php-dev \
    && /opt/docker/bin/bootstrap.sh \
    && /usr/local/bin/docker-image-cleanup

EXPOSE 80 443
