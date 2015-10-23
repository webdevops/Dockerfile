#++++++++++++++++++++++++++++++++++++++
# Ubuntu 15.10 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM ubuntu:15.10
MAINTAINER info@webdevops.io

# Ensure UTF-8
RUN locale-gen en_US.UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# Install apt-add-repository
RUN set -x \
    && apt-get update \
    && apt-get install -y \
        software-properties-common \
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
        openssh-server \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Deploy scripts/configurations
RUN mkdir -p /opt/docker/
COPY bin     /opt/docker/bin/
COPY etc     /opt/docker/etc/

# Make executable
RUN chmod +x /opt/docker/bin/*

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["noop"]
