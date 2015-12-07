#++++++++++++++++++++++++++++++++++++++
# CentOS 7 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM centos:7
MAINTAINER info@webdevops.io

# Add common scripts (from _localscripts)
ADD scripts.tar ./

# System update
RUN /usr/local/bin/yum-upgrade

# Install ansible
RUN /usr/local/bin/yum-install \
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
