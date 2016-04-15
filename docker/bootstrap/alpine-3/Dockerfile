#++++++++++++++++++++++++++++++++++++++
# Alpine 3 Bootstrap Docker container
#++++++++++++++++++++++++++++++++++++++

FROM alpine:3.3
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0

# Ensure UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# Add common scripts (from _localscripts)
ADD scripts.tar /

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
    && pip install --upgrade pip \
    && pip install ansible
