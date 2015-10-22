#++++++++++++++++++++++++++++++++++++++
# Ubuntu 12.04 Docker container
#++++++++++++++++++++++++++++++++++++++

FROM ubuntu:12.04
MAINTAINER info@webdevops.io

# Ensure UTF-8
RUN locale-gen en_US.UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# Install apt-add-repository
RUN set -x \
    && apt-get update \
    && apt-get install -y \
        python-software-properties \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Install ansible
RUN set -x \
    && apt-add-repository ppa:ansible/ansible \
    && apt-get update \
    && apt-get install -y \
        ansible \
        python-apt \
        aptitude \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Install common tools
RUN set -x \
    && apt-get update \
    && apt-get install -y \
        lsb-release \
        sudo \
        zip \
        unzip \
        bzip2 \
        wget \
        curl \
        net-tools \
        moreutils \
        dnsutils \
        openssh-client \
        rsync \
        git \
        cron \
        anacron \
        supervisor \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y
