#++++++++++++++++++++++++++++++++++++++
# CentOS 7 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM centos:7
MAINTAINER info@webdevops.io

ENV APPLICATION_USER  application
ENV APPLICATION_GROUP application
ENV APPLICATION_UID   1000
ENV APPLICATION_GID   1000

# System update
RUN yum upgrade -y \
    && yum clean all

# Install ansible
RUN yum clean all
RUN yum -y install \
        epel-release \
        PyYAML \
        python-jinja2 \
        python-httplib2 \
        python-keyczar \
        python-paramiko \
        python-setuptools \
        python-setuptools-devel \
    && easy_install pip \
    && pip install ansible \
    && yum clean all

# Install services
RUN yum -y install \
        cronie \
        supervisor \
        openssh-server \
        mysql \
    && yum clean all

# Install tools
RUN yum -y install \
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
        vim \
    && yum clean all

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/bootstrap.sh

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["noop"]
