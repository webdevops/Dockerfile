#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/apache-dev:ubuntu-16.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/apache:ubuntu-16.04

ENV WEB_NO_CACHE_PATTERN="\.(css|js|gif|png|jpg|svg|json|xml)$"

COPY conf/ /opt/docker/

RUN set -x \
     \
    && docker-run-bootstrap \
    && docker-image-cleanup

EXPOSE 80 443
