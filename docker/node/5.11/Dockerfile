#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/node:5.11
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM node:5.11

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=7
LABEL io.webdevops.version=0.50.5

###############################################################################
# Bootstrap
###############################################################################

# Ensure UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

ADD baselayout.tar /

# Init bootstrap (and install ansible)
RUN export DEBIAN_FRONTEND=noninteractive \
    && set -x \
    # Fix root terminal
    && echo "export TERM=xterm" >> /root/.bashrc \
    # Enable non-free
    && sed -ri 's/(deb.*\/debian jessie main)/\1 contrib non-free /' -- /etc/apt/sources.list \
    # System updatei
    && /usr/local/bin/apt-upgrade \
    # Base stuff
    && /usr/local/bin/apt-install \
        lsb-release \
        ca-certificates \
        locales \
    # Generate base locale
    && echo "en_US.UTF-8 UTF-8" > /etc/locale.gen \
    && locale-gen \
    && /usr/local/bin/apt-install \
        # Install ansible
        python-minimal \
        python-setuptools \
        python-pip \
        python-paramiko \
        python-jinja2 \
        python-dev \
        libffi-dev \
        libssl-dev \
        build-essential \
    && pip install --upgrade pip \
    && hash -r \
    && pip install ansible \
    && chmod 750 /usr/local/bin/ansible* \
    # Cleanup
    && apt-get purge -y -f --force-yes \
        python-dev \
        build-essential \
        libssl-dev \
        libffi-dev \
    && apt-get autoremove -y -f \
    && apt-get clean -y

###############################################################################
# Base
###############################################################################

ENV DOCKER_CONF_HOME /opt/docker/

COPY conf/ /opt/docker/

# Install packages
RUN chmod +x /opt/docker/bin/* \
    && /usr/local/bin/apt-install \
        supervisor \
        syslog-ng \
        syslog-ng-core \
        logrotate \
        cron \
        wget \
        curl \
        net-tools \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-base \
    && /opt/docker/bin/bootstrap.sh
