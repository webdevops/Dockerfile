#++++++++++++++++++++++++++++++++++++++
# CentOS 7 Bootstrap Docker container
#++++++++++++++++++++++++++++++++++++++

FROM centos:7
### BEGIN GENERATED CONTENT :: general:version ###
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0
### END GENERATED CONTENT :: general:version ###

# Add baselayout (from baselayout/)
ADD baselayout.tar /

# Init bootstrap (and install ansible)
RUN set -x \
    # Fix root terminal
    && echo "export TERM=xterm" >> /root/.bashrc \
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

