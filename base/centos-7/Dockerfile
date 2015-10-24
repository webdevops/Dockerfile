#++++++++++++++++++++++++++++++++++++++
# CentOS 7 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM centos:7
MAINTAINER info@webdevops.io

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

# Make executable
RUN chmod +x /opt/docker/bin/*

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["noop"]
