#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:centos-7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:centos-7

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm

COPY conf/ /opt/docker/

# Install php environment
RUN /usr/local/bin/yum-install \
        # Install tools
        ImageMagick \
        GraphicsMagick \
        ghostscript \
        # Install php (cli/fpm)
        php-cli \
        php-fpm \
        php-json \
        php-intl \
        php-curl \
        php-mysqlnd \
        php-memcached \
        php-mcrypt \
        php-gd \
        php-mbstring \
        php-bcmath \
        php-soap \
        sqlite \
        php-xmlrpc \
        php-xsl \
        geoip \
        php-ldap \
        php-memcache \
        php-pecl-redis \
        ImageMagick \
        ImageMagick-devel \
        ImageMagick-perl \
        php-pear \
        php-pecl-apcu \
        php-devel \
        gcc \
        php-pear \
    && pear channel-update pear.php.net \
    && pear upgrade-all \
    && pear config-set auto_discover 1 \
    && pecl install imagick \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    # Cleanup
    && yum erase -y php-devel gcc \
    # Enable php services
    && docker-service-enable syslog cron \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 9000
