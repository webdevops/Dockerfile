#++++++++++++++++++++++++++++++++++++++
# Ubuntu 15.04 Bootstrap Docker container
#++++++++++++++++++++++++++++++++++++++

FROM ubuntu:15.04
MAINTAINER info@webdevops.io

# Ensure UTF-8
RUN locale-gen en_US.UTF-8
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
    # Install apt-add-repository
    && /usr/local/bin/apt-install \
        software-properties-common \
    && apt-add-repository multiverse \
    # Install ansible
    && apt-add-repository ppa:ansible/ansible \
    && /usr/local/bin/apt-install \
        ansible \
        python-apt \
        aptitude
