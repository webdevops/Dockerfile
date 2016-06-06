#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:debian-9
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:debian-9

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=7
LABEL io.webdevops.version=0.50.3

COPY conf/ /opt/docker/

# Install development environment
RUN /usr/local/bin/apt-install \
        # Install tools
        graphviz \
        # Install php development stuff
        php-xdebug \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-php-dev \
    && /opt/docker/bin/bootstrap.sh

