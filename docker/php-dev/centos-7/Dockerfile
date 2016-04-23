#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:centos-7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:centos-7

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0

COPY conf/ /opt/docker/

# Install development environment
RUN /usr/local/bin/yum-install \
        # Install tools
        graphviz \
        # Install php development stuff
        php-pecl-xdebug \
    && /opt/docker/bin/provision-run.sh webdevops-php-dev \
    && /opt/docker/bin/bootstrap.sh

