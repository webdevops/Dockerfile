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

RUN /usr/local/bin/apk-install shadow \
    && /usr/local/bin/apk-install \
        # Install common tools
        zip \
        unzip \
        bzip2 \
        drill \
        ldns \
        openssh-client \
        rsync \
        git \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup
