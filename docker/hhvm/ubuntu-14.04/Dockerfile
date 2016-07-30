#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/hhvm:ubuntu-14.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:ubuntu-14.04

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=0.55.3

ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

COPY conf/ /opt/docker/

# Install hhvm environment
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
    && apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0x5a16e7281be7a449 \
    && add-apt-repository "deb http://dl.hhvm.com/ubuntu $(lsb_release -sc) main" \
    && /usr/local/bin/apt-install \
        hhvm \
        imagemagick \
        graphicsmagick \
    && /usr/bin/update-alternatives --install /usr/bin/php php /usr/bin/hhvm 60 \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-hhvm \
    && /opt/docker/bin/bootstrap.sh


EXPOSE 9000

