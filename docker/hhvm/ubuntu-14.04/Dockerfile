#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/hhvm:ubuntu-14.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:ubuntu-14.04

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm \
    WEB_PHP_TIMEOUT=600 \
    WEB_PHP_SOCKET=""

COPY conf/ /opt/docker/

RUN set -x \
    # Install hhvm environment
    && export DEBIAN_FRONTEND=noninteractive \
    && apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xB4112585D386EB94 \
    && echo "deb http://dl.hhvm.com/ubuntu $(docker-image-info dist-codename) main" >> /etc/apt/sources.list \
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

