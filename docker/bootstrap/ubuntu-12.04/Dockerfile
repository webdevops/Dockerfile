#++++++++++++++++++++++++++++++++++++++
# Ubuntu 12.04 Bootstrap Docker container
#++++++++++++++++++++++++++++++++++++++

FROM ubuntu:12.04
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0

# Ensure UTF-8
RUN locale-gen en_US.UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# Add common scripts (from _localscripts)
ADD scripts.tar /

# Init bootstrap (and install ansible)
RUN export DEBIAN_FRONTEND=noninteractive \
    && set -x \
    # Add multiverse
    && echo "deb http://archive.ubuntu.com/ubuntu/ precise-security multiverse" >> /etc/apt/sources.list \
    && echo "deb-src http://archive.ubuntu.com/ubuntu/ precise-security multiverse" >> /etc/apt/sources.list \
    && echo "deb http://archive.ubuntu.com/ubuntu/ precise multiverse" >> /etc/apt/sources.list \
    && echo "deb-src http://archive.ubuntu.com/ubuntu/ precise multiverse" >> /etc/apt/sources.list \
    # Fix root terminal
    && echo "export TERM=xterm" >> /root/.bashrc \
    # System update
    && /usr/local/bin/apt-upgrade \
    # Install base stuff
    && /usr/local/bin/apt-install \
        python-software-properties \
        lsb-release \
        ca-certificates \
    # Install ansible
        python-dev \
        python-setuptools \
        python-pip \
    && pip install ansible

