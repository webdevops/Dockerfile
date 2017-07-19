#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:alpine-php5
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:alpine

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm

COPY conf/ /opt/docker/

# Install php environment
RUN /usr/local/bin/apk-install \
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
        php5-mysql \
        php5-mysqli \
        php5-pdo_mysql \
        php5-pdo_sqlite \
        php5-mcrypt \
        php5-gd \
        php5-bcmath \
        php5-soap \
        php5-sqlite3 \
        php5-bz2 \
        php5-calendar \
        php5-ctype \
        php5-pcntl \
        php5-posix \
        php5-sockets \
        php5-sysvmsg \
        php5-sysvsem \
        php5-sysvshm \
        php5-xmlreader \
        php5-exif \
        php5-ftp \
        php5-gettext \
        php5-iconv \
        php5-zip \
        php5-zlib \
        php5-shmop \
        php5-wddx \
        sqlite \
        php5-xmlrpc \
        php5-xsl \
        geoip \
        php5-ldap \
        # php5-memcache \
        # php5-redis \
        php5-pear \
        php5-phar \
        php5-openssl \
        # php5-apcu \
    && ln -s /usr/bin/php5      /usr/bin/php \
    && ln -s /usr/sbin/php-fpm5 /usr/sbin/php-fpm \
    && ln -s /usr/bin/phpize5 /usr/sbin/phpize \
    && pear channel-update pear.php.net \
    && pear upgrade-all \
    && pear config-set auto_discover 1 \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    # PECL workaround, see webdevops/Dockerfile#78
    && sed -i "s/ -n / /" $(which pecl) \
    # && apk-install gcc php5-dev autoconf --virtual .pecl-deps \
    # && go-replace  -v -s 'expr -- [$]0 : "'\''.*"' -r 'expr " [$]0" : " '\''.*"' -- /usr/lib/php/build/acinclude.m4
    # && pecl install apcu-4.0.11 \
    # && apk del .pecl-deps \
    # Enable php services
    && docker-service-enable syslog cron \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 9000
