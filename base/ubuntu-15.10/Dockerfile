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

# Add common scripts (from _localscripts)
ADD scripts.tar ./

# System update
RUN /usr/local/bin/apt-upgrade

# Install apt-add-repository and enable multiverse
RUN /usr/local/bin/apt-install \
        software-properties-common \
    && apt-add-repository multiverse

# Install ansible
RUN /usr/local/bin/apt-install \
        ansible \
        python-apt \
        aptitude

# Install services
RUN /usr/local/bin/apt-install \
        supervisor \
        syslog-ng \
        syslog-ng-core \
        logrotate \
        openssh-server \
        mysql-client \
        cron \
        dnsmasq \
        postfix

# Install common tools
RUN /usr/local/bin/apt-install \
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
        vim

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role webdevops-base \
    && bash /opt/docker/bin/control.sh provision.role.finish webdevops-cleanup \
    && bash /opt/docker/bin/bootstrap.sh

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["noop"]
