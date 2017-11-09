#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base-app:alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:alpine

ENV APPLICATION_USER=application \
    APPLICATION_GROUP=application \
    APPLICATION_PATH=/app \
    APPLICATION_UID=1000 \
    APPLICATION_GID=1000

COPY conf/ /opt/docker/

RUN set -x \
    && apk-install shadow \
    && apk-install \
        # Install common tools
        zip \
        unzip \
        bzip2 \
        drill \
        ldns \
        openssh-client \
        rsync \
        git \
    && docker-run-bootstrap \
    && docker-image-cleanup
