#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-nginx:alpine-3-php7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:alpine-3-php7

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0

ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

COPY conf/ /opt/docker/

# Install nginx
RUN /usr/local/bin/apk-install \
        nginx \
    && /opt/docker/bin/provision add --tag bootstrap webdevops-nginx webdevops-php-nginx \
    && /opt/docker/bin/bootstrap.sh


EXPOSE 80 443
