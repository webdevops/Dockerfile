#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/hhvm:ubuntu-16.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:ubuntu-16.04

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm \
    WEB_PHP_TIMEOUT=600 \
    WEB_PHP_SOCKET=""

COPY conf/ /opt/docker/

RUN set -x \
    # Install hhvm environment
    && apt-install \
        hhvm \
        imagemagick \
        graphicsmagick \
        ghostscript \
    && /usr/bin/update-alternatives --install /usr/bin/php php /usr/bin/hhvm 60 \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    && docker-run-bootstrap \
    && docker-image-cleanup

EXPOSE 9000

