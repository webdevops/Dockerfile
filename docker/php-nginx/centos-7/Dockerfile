#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-nginx:centos-7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:centos-7

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm
ENV WEB_PHP_SOCKET=127.0.0.1:9000
ENV WEB_PHP_TIMEOUT=600

COPY conf/ /opt/docker/

# Install tools
RUN /usr/local/bin/yum-install \
        nginx \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 80 443
