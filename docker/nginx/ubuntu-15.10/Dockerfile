#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/nginx:ubuntu-15.10
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:ubuntu-15.10

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm

COPY conf/ /opt/docker/

# Install nginx
RUN /usr/local/bin/apt-install \
        nginx \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-nginx \
    && /opt/docker/bin/bootstrap.sh \
    && /usr/local/bin/docker-image-cleanup

EXPOSE 80 443
