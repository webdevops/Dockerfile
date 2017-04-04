#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:debian-8
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:debian-8

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.5.0

ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

COPY conf/ /opt/docker/

# Install php environment
RUN /usr/local/bin/apt-install \
        # Install tools
        imagemagick \
        graphicsmagick \
        ghostscript \
        # Install php (cli/fpm)
        php5-cli \
        php5-fpm \
        php5-json \
        php5-intl \
        php5-curl \
        php5-mysqlnd \
        php5-mcrypt \
        php5-gd \
        php5-sqlite \
        php5-xmlrpc \
        php5-xsl \
        php5-geoip \
        php5-ldap \
        php5-memcache \
        php5-memcached \
        php5-imagick \
        php5-redis \
        php5-apcu \
        php-pear \
    && pear channel-update pear.php.net \
    && pear upgrade-all \
    && pear config-set auto_discover 1 \
    && ln -sf /etc/php5/mods-available/mcrypt.in /etc/php5/cli/conf.d/20-mcrypt.ini \
    && ln -sf /etc/php5/mods-available/mcrypt.in /etc/php5/fpm/conf.d/20-mcrypt.ini \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    # Enable php services
    && /opt/docker/bin/control.sh service.enable cron \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-php \
    && /opt/docker/bin/bootstrap.sh \
    && /usr/local/bin/docker-image-cleanup

EXPOSE 9000
