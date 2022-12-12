#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-nginx:8.2
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:8.2

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm \
    WEB_PHP_TIMEOUT=600 \
    WEB_PHP_SOCKET=""
ENV WEB_PHP_SOCKET=127.0.0.1:9000
ENV SERVICE_NGINX_CLIENT_MAX_BODY_SIZE="50m"

COPY conf/ /opt/docker/

RUN set -x \
    # Install nginx
    && printf "deb http://nginx.org/packages/debian/ $(docker-image-info dist-codename)  nginx\n deb-src http://nginx.org/packages/debian/ $(docker-image-info dist-codename) nginx" \
    >> /etc/apt/sources.list \
    && curl -L https://nginx.org/keys/nginx_signing.key | apt-key add - \
    && apt-install \
    nginx \
    && docker-run-bootstrap \
    && docker-image-cleanup

EXPOSE 80 443
