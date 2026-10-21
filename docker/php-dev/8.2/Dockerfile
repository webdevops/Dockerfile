#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:8.2
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:8.2

COPY conf/ /opt/docker/

RUN --mount=type=bind,from=ghcr.io/php/pie:bin,source=/pie,target=/usr/local/bin/pie \
    set -x \
    # Install development environment
    && BLACKFIRE_INSTALL_METHOD=raw bash -c "$(curl -L https://installer.blackfire.io/installer.sh)" \
    && blackfire php:install \
    && echo 'deb https://packages.tideways.com/apt-packages-main any-version main' | tee /etc/apt/sources.list.d/tideways.list \
    && wget -qO - https://packages.tideways.com/key.gpg | apt-key add - \
    && apt-install tideways-php tideways-daemon \
    && PIE_WORKING_DIRECTORY=/tmp/pie pie install --no-interaction --no-cache \
        --skip-enable-extension --make-parallel-jobs="$(nproc)" xdebug/xdebug \
    && rm -rf /tmp/pie \
    && docker-php-ext-enable xdebug \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap \
    && docker-image-cleanup
