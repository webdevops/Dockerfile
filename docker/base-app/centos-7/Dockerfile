#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base-app:centos-7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:centos-7

ENV APPLICATION_USER=application \
    APPLICATION_GROUP=application \
    APPLICATION_PATH=/app \
    APPLICATION_UID=1000 \
    APPLICATION_GID=1000

COPY conf/ /opt/docker/

RUN set -x \
    # Install services
    && yum-install \
        # Install tools
        zip \
        unzip \
        bzip2 \
        moreutils \
        dnsutils \
        bind-utils \
        rsync \
        git \
    && /usr/local/bin/generate-locales \
    && docker-run-bootstrap \
    && docker-image-cleanup
