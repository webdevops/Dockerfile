#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/apache-dev:alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/apache:alpine-3

ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

COPY conf/ /opt/docker/

# Install apache
RUN echo \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-apache-dev \
    && /opt/docker/bin/bootstrap.sh \
    && /usr/local/bin/docker-image-cleanup

EXPOSE 80 443
