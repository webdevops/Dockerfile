#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:7.3
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:7.3

COPY conf/ /opt/docker/

RUN set -x \
    # Install development environment
    && wget -O - https://packagecloud.io/gpg.key | apt-key add - \
    && pecl install xdebug-2.7.0beta1 \
    && echo "zend_extension=xdebug.so" > /usr/local/etc/php/conf.d/xdebug.ini \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap \
    && docker-image-cleanup
