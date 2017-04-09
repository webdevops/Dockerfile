#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:debian-8-php7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:debian-8-php7

COPY conf/ /opt/docker/

# Install development environment
RUN wget -O - https://packagecloud.io/gpg.key | apt-key add - \
    && echo "deb https://packages.blackfire.io/debian any main" | tee /etc/apt/sources.list.d/blackfire.list \
    && /usr/local/bin/apt-install \
        # Install tools
        graphviz \
        # Install php development stuff
        # php7.0-xdebug \ # Is currently not available
        blackfire-php \
        blackfire-agent \
    # Enable php development services
    && docker-service-enable ssh \
    && docker-service-enable postfix \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-dev --role webdevops-php-dev \
    && /opt/docker/bin/bootstrap.sh \
    && /usr/local/bin/docker-image-cleanup
