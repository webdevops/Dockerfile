#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-dev:7.4
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:7.4

COPY conf/ /opt/docker/

RUN set -x \
    
    && pecl install xdebug-2.8.0beta1 \
    && echo "zend_extension=xdebug.so" > /usr/local/etc/php/conf.d/xdebug.ini \
    # Enable php development services
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-service enable ssh \
    && docker-run-bootstrap \
    && docker-image-cleanup
