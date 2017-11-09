#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:centos-7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:centos-7

COPY conf/ /opt/docker/

RUN set -x \
    # Install development environment
    && wget -O - "https://packages.blackfire.io/fedora/blackfire.repo" | tee /etc/yum.repos.d/blackfire.repo \
    && yum-install \
        # Install tools
        graphviz \
        # Install php development stuff
        php-pecl-xdebug \
        blackfire-php \
        blackfire-agent \
        # Tools
        nano \
        vim \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap \
    && docker-image-cleanup
