#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:alpine-3
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:alpine-3

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=0.55.1

COPY conf/ /opt/docker/

# Install development environment
RUN /usr/local/bin/apk-install \
        # Install tools
        graphviz \
        gnupg \
        # Install php development stuff
        php5-xdebug \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-php-dev \
    && /opt/docker/bin/bootstrap.sh

