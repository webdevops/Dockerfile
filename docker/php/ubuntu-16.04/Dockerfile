#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:ubuntu-16.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:ubuntu-16.04

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0

ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

COPY conf/ /opt/docker/

# Install php environment
RUN /usr/local/bin/apt-install \
        # Install tools
        imagemagick \
        graphicsmagick \
        # Install php (cli/fpm)
        php5-cli \
        php5-fpm \
        php5-json \
        php5-intl \
        php5-curl \
        php5-mysqlnd \
        #php5-xdebug \
        php5-mcrypt \
        php5-gd \
        php5-sqlite \
        php5-xmlrpc \
        php5-xsl \
        php5-geoip \
        php5-ldap \
        php5-memcache \
        php5-imagick \
    && ln -sf /etc/php5/mods-available/mcrypt.in /etc/php5/cli/conf.d/20-mcrypt.ini \
    && ln -sf /etc/php5/mods-available/mcrypt.in /etc/php5/fpm/conf.d/20-mcrypt.ini \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    && /opt/docker/bin/provision-run.sh webdevops-php \
    && /opt/docker/bin/bootstrap.sh


EXPOSE 9000
