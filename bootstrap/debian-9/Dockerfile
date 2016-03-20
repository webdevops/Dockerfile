#++++++++++++++++++++++++++++++++++++++
# Debian 9 Bootstrap Docker container
#++++++++++++++++++++++++++++++++++++++

FROM debian:stretch
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=5
LABEL io.webdevops.version=0.21.0

# Ensure UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# Add common scripts (from _localscripts)
ADD scripts.tar /

# Init bootstrap (and install ansible)
RUN export DEBIAN_FRONTEND=noninteractive \
    && set -x \
    # Fix root terminal
    && echo "export TERM=xterm" >> /root/.bashrc \
    # System update
    && /usr/local/bin/apt-upgrade \
    # Base stuff
    && /usr/local/bin/apt-install \
        lsb-release \
        ca-certificates \
        # Generate locales
        locales-all \
    # Install ansible
        python-dev \
        python-setuptools \
        python-pip \
    && pip install ansible

