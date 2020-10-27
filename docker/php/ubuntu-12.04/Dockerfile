#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:ubuntu-12.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:ubuntu-12.04

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm \
    WEB_PHP_TIMEOUT=600 \
    WEB_PHP_SOCKET=""
ENV COMPOSER_VERSION="2"

COPY conf/ /opt/docker/

RUN set -x \
    # Install php environment
    && apt-install \
        # Install tools
        imagemagick \
        graphicsmagick \
        ghostscript \
        # Install php (cli/fpm)
        php5-cli \
        php5-fpm \
        php5-json \
        php5-intl \
        php5-imap \
        php5-curl \
        php5-mysqlnd \
        php5-mongodb \
        php5-mcrypt \
        php5-gd \
        php5-sqlite \
        php5-pgsql \
        php5-xmlrpc \
        php5-xsl \
        php5-geoip \
        php5-ldap \
        php5-memcache \
        #php-memcached \
        php5-imagick \
        #php5-redis \
        php-pear \
    && pecl channel-update pecl.php.net \
    # Temporarily disable pear due to https://twitter.com/pear/status/1086634389465956352
    # && pear channel-update pear.php.net \
    # && pear upgrade-all \
    && pear config-set auto_discover 1 \
    && ln -sf /etc/php5/mods-available/mcrypt.in /etc/php5/cli/conf.d/20-mcrypt.ini \
    && ln -sf /etc/php5/mods-available/mcrypt.in /etc/php5/fpm/conf.d/20-mcrypt.ini \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer2 \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer1 --1 \
    && ln -sf /usr/local/bin/composer2 /usr/local/bin/composer \
    # Enable php services
    && docker-service enable syslog \
    && docker-service enable cron \
    && docker-run-bootstrap \
    && docker-image-cleanup

EXPOSE 9000
