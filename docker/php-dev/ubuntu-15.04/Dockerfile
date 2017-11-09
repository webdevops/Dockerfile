#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:ubuntu-15.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:ubuntu-15.04

COPY conf/ /opt/docker/

RUN set -x \
    # Install development environment
    && apt-install \
        gnupg \
    && wget -O - https://packagecloud.io/gpg.key | apt-key add - \
    && echo "deb https://packages.blackfire.io/debian any main" | tee /etc/apt/sources.list.d/blackfire.list \
    && apt-install \
        # Install tools
        graphviz \
        # Install php development stuff
        php5-xdebug \
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
