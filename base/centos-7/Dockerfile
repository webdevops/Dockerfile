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
        cronie \
        supervisor \
        openssh-server \
    && yum clean all

# Deploy scripts/configurations
RUN mkdir -p /opt/docker/
COPY bin     /opt/docker/bin/
COPY etc     /opt/docker/etc/

# Make executable
RUN chmod +x /opt/docker/bin/*

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["noop"]
