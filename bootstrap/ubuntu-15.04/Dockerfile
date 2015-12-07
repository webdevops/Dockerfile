#++++++++++++++++++++++++++++++++++++++
# Ubuntu 15.04 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM ubuntu:15.04
MAINTAINER info@webdevops.io

# Ensure UTF-8
RUN locale-gen en_US.UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# Add common scripts (from _localscripts)
ADD scripts.tar ./

# System update
RUN /usr/local/bin/apt-upgrade

# Install apt-add-repository and enable multiverse
RUN /usr/local/bin/apt-install \
        software-properties-common \
    && apt-add-repository multiverse

# Install ansible
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
    && apt-add-repository ppa:ansible/ansible \
    && /usr/local/bin/apt-install \
        ansible \
        python-apt \
        aptitude
