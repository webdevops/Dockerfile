#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/hhvm:ubuntu-14.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:ubuntu-14.04

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm

COPY conf/ /opt/docker/

# Install hhvm environment
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
    && apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0x5a16e7281be7a449 \
    && echo "deb http://dl.hhvm.com/ubuntu $(docker-image-info dist-codename) main" >> /etc/apt/sources.list \
    && /usr/local/bin/apt-install \
        hhvm \
        imagemagick \
        graphicsmagick \
        ghostscript \
    && /usr/bin/update-alternatives --install /usr/bin/php php /usr/bin/hhvm 60 \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 9000

