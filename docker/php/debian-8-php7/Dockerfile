#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:debian-8-php7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:debian-8

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0

ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

COPY conf/ /opt/docker/

# Install php environment
RUN echo "deb http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list \
    && echo "deb-src http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list \
    && wget -O- https://www.dotdeb.org/dotdeb.gpg | apt-key add  - \
    && /usr/local/bin/apt-install \
        # Install tools
        imagemagick \
        graphicsmagick \
        # Install php (cli/fpm)
        php7.0-cli \
        php7.0-fpm \
        php7.0-json \
        php7.0-intl \
        php7.0-curl \
        php7.0-mysql \
        php7.0-mcrypt \
        php7.0-gd \
        php7.0-sqlite3 \
        php7.0-ldap \
        php7.0-opcache \
        php7.0-xmlrpc \
        php7.0-xsl \
        php7.0-bz2 \
        php7.0-redis \
        php7.0-memcached \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    && /opt/docker/bin/provision-run.sh webdevops-php \
    && /opt/docker/bin/bootstrap.sh


EXPOSE 9000
