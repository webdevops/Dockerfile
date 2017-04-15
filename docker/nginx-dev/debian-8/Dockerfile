#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/nginx-dev:debian-8
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/nginx:debian-8

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
