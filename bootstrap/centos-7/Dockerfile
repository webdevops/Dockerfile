#++++++++++++++++++++++++++++++++++++++
# CentOS 7 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM centos:7
MAINTAINER info@webdevops.io

# Add common scripts (from _localscripts)
ADD scripts.tar ./

# Init bootstrap (and install ansible)
RUN set -x \
    # Fix root terminal
    && echo "export TERM=xterm >> /root/.bashrc" \
    # System update
    && /usr/local/bin/yum-upgrade \
    # Install ansible
    && /usr/local/bin/yum-install \
        epel-release \
        PyYAML \
        python-jinja2 \
        python-httplib2 \
        python-keyczar \
        python-paramiko \
        python-setuptools \
        python-setuptools-devel \
    && easy_install pip \
    && pip install ansible
