#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:ubuntu-14.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:ubuntu-14.04

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm

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
    && docker-service-enable syslog cron \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 9000
