#++++++++++++++++++++++++++++++++++++++
# Ubuntu 15.04 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM ubuntu:15.04
MAINTAINER info@webdevops.io

ENV APPLICATION_USER  application
ENV APPLICATION_GROUP application
ENV APPLICATION_UID   1000
ENV APPLICATION_GID   1000

# Ensure UTF-8
RUN locale-gen en_US.UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# System update
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
    && apt-get update \
    && apt-get dist-upgrade -y \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Install apt-add-repository and enable multiverse
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
    && apt-get update \
    && apt-get install -y \
        software-properties-common \
    && apt-add-repository multiverse \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Install ansible
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
    && apt-add-repository ppa:ansible/ansible \
    && apt-get update \
    && apt-get install -y \
        ansible \
        python-apt \
        aptitude \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Install services
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
    && apt-get update \
    && apt-get install -f -y \
        supervisor \
        syslog-ng \
        syslog-ng-core \
        logrotate \
        openssh-server \
        mysql-client \
        cron \
        dnsmasq \
        postfix \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Install common tools
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
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
        nano \
        vim \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role webdevops-base \
    && bash /opt/docker/bin/control.sh provision.role.finish webdevops-cleanup \
    && bash /opt/docker/bin/bootstrap.sh

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["noop"]
