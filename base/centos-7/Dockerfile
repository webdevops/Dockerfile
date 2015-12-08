#++++++++++++++++++++++++++++++++++++++
# CentOS 7 Base Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:centos-7
MAINTAINER info@webdevops.io

ENV APPLICATION_USER  application
ENV APPLICATION_GROUP application
ENV APPLICATION_UID   1000
ENV APPLICATION_GID   1000

# Install services
RUN /usr/local/bin/yum-install \
        cronie \
        supervisor \
        syslog-ng \
        logrotate \
        openssh-server \
        mysql \
        dnsmasq \
        postfix

# Install tools
RUN /usr/local/bin/yum-install \
        sudo \
        zip \
        unzip \
        bzip2 \
        wget \
        curl \
        net-tools \
        moreutils \
        dnsutils \
        bind-utils \
        rsync \
        git \
        pygpgme \
        nano \
        vim

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role webdevops-base \
    && bash /opt/docker/bin/control.sh provision.role.finish webdevops-cleanup \
    && bash /opt/docker/bin/bootstrap.sh

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["noop"]
