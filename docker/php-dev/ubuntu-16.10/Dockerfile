#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:ubuntu-16.10
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:ubuntu-16.10

COPY conf/ /opt/docker/

RUN set -x \
    # Install development environment
    && apt-install \
        gnupg \
    && wget -q -O - https://packages.blackfire.io/gpg.key | apt-key add - \
    && echo "deb https://packages.blackfire.io/debian any main" | tee /etc/apt/sources.list.d/blackfire.list \
    && apt-install \
        # Install tools
        graphviz \
        # Install php development stuff
        php-xdebug \
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
