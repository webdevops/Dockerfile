#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/bootstrap:ubuntu-15.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM ubuntu:15.04

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.2.3

# Ensure UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

ADD baselayout.tar /

# Init bootstrap (and install ansible)
RUN export DEBIAN_FRONTEND=noninteractive \
    && set -x \
    # Fix root terminal
    && echo "export TERM=xterm" >> /root/.bashrc \
    # Generate base locale
    && locale-gen en_US.UTF-8 \
    # System update
    && /usr/local/bin/apt-upgrade \
    # Install base stuff
    && /usr/local/bin/apt-install \
        software-properties-common \
        lsb-release \
        ca-certificates \
        apt-transport-https \
    && apt-add-repository multiverse \
    # Install ansible
    && /usr/local/bin/apt-install \
        python-minimal \
        python-setuptools \
        python-pip \
        python-paramiko \
        python-jinja2 \
        python-cffi \
        python-dev \
        libffi-dev \
       	libssl-dev \
        build-essential \
    && pip install --upgrade pip \
    && hash -r \
    && pip install ansible \
    && chmod 750 /usr/local/bin/ansible* \
    # Cleanup
    && apt-get purge -y -f --force-yes \
        python-dev \
        build-essential \
        libssl-dev \
        libffi-dev \
    && apt-get autoremove -y -f \
    && apt-get clean -y \
    && find /var/log/ -mindepth 1 -delete
