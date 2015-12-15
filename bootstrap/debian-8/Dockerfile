#++++++++++++++++++++++++++++++++++++++
# Debian 8 Bootstrap Docker container
#++++++++++++++++++++++++++++++++++++++

FROM debian:8
MAINTAINER info@webdevops.io

# Ensure UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# Add common scripts (from _localscripts)
ADD scripts.tar ./

# Init bootstrap (and install ansible)
RUN export DEBIAN_FRONTEND=noninteractive \
    && set -x \
    # Fix root terminal
    && echo "export TERM=xterm" >> /root/.bashrc \
    # System update
    && /usr/local/bin/apt-upgrade \
    # Generate locales
    && /usr/local/bin/apt-install \
        locales-all \
    # Install ansible
    && /usr/local/bin/apt-install \
        python-dev \
        python-pip \
    && pip install ansible
