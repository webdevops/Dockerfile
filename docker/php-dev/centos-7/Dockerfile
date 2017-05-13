#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:centos-7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:centos-7

COPY conf/ /opt/docker/

# Install development environment
RUN wget -O - "https://packages.blackfire.io/fedora/blackfire.repo" | tee /etc/yum.repos.d/blackfire.repo \
    && /usr/local/bin/yum-install \
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
    && docker-service-enable syslog ssh postfix \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup
