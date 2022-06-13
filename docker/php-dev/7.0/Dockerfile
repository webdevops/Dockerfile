#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:7.0
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:7.0

COPY conf/ /opt/docker/

RUN set -x \
    # Install development environment
    && bash -c "$(curl -L https://installer.blackfire.io/installer.sh)" \
    && blackfire php:install \
    && echo 'deb https://packages.tideways.com/apt-packages-main any-version main' | tee /etc/apt/sources.list.d/tideways.list \
    && wget -qO - https://packages.tideways.com/key.gpg | apt-key add - \
    && apt-install tideways-php tideways-daemon \
    && pecl install xdebug-2.8.1 \
    && docker-php-ext-enable xdebug \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap \
    && docker-image-cleanup
