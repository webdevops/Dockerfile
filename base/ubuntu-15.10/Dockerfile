#++++++++++++++++++++++++++++++++++++++
# Ubuntu 15.10 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM ubuntu:15.10
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
RUN set -x \
    && apt-get update \
    && apt-get dist-upgrade -y \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Install apt-add-repository and enable multiverse
RUN set -x \
    && apt-get update \
    && apt-get install -y \
        software-properties-common \
    && apt-add-repository multiverse \
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

# Install services
RUN set -x \
    && apt-get update \
    && apt-get install -y \
        supervisor \
        openssh-server \
        mysql-client \
        cron \
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
        nano \
        vim \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/bootstrap.sh

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["noop"]
