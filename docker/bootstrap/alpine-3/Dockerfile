#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/bootstrap:alpine-3
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM alpine:3.4

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=0.54.0

# Ensure UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

ADD baselayout.tar /

# Init bootstrap (and install ansible)
RUN set -x \
    # Fix root terminal
    && echo "export TERM=xterm" >> /root/.bashrc \
    # Add testing
    && echo http://nl.alpinelinux.org/alpine/edge/testing >> /etc/apk/repositories \
    # System update
    && /usr/local/bin/apk-upgrade \
    # Install base stuff
    && /usr/local/bin/apk-install \
        bash \
        shadow \
        ca-certificates \
    # Install ansible
    && /usr/local/bin/apk-install \
        python \
        python-dev \
        py-setuptools \
        py-crypto \
        py-pip \
        py-cparser \
        py-cryptography \
        py-markupsafe \
        py-cffi \
        py-yaml \
        py-jinja2 \
        py-paramiko \
    && pip install --upgrade pip \
    && hash -r \
    && pip install ansible \
    && chmod 750 /usr/bin/ansible* \
    # Cleanup
    && apk del python-dev
