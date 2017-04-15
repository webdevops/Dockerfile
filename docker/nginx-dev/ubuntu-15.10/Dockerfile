#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/nginx-dev:ubuntu-15.10
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/nginx:ubuntu-15.10

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm

COPY conf/ /opt/docker/

# Install apache
RUN echo \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-nginx-dev \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 80 443
