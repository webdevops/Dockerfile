#++++++++++++++++++++++++++++++++++++++
# Ubuntu 16.04 Bootstrap Docker container
#++++++++++++++++++++++++++++++++++++++

FROM ubuntu:16.04
### BEGIN GENERATED CONTENT :: general:version ###
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0
### END GENERATED CONTENT :: general:version ###

# Ensure UTF-8
RUN locale-gen en_US.UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# Add baselayout (from baselayout/)
ADD baselayout.tar /

# Init bootstrap (and install ansible)
RUN export DEBIAN_FRONTEND=noninteractive \
    && set -x \
    # Fix root terminal
    && echo "export TERM=xterm" >> /root/.bashrc \
    # System update
    && /usr/local/bin/apt-upgrade \
    # Install base stuff
    && /usr/local/bin/apt-install \
        software-properties-common \
        lsb-release \
        ca-certificates \
    && apt-add-repository multiverse \
    # Install ansible
    && /usr/local/bin/apt-install \
        python-dev \
        python-setuptools \
        python-pip \
    && pip install ansible \
    ## workaround for ubuntu 16.04
    && chmod +x /usr/local/bin/ansible*
