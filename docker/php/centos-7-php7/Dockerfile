#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:centos-7-php7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:centos-7

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm \
    WEB_PHP_TIMEOUT=600 \
    WEB_PHP_SOCKET=""
ENV COMPOSER_VERSION="2"

COPY conf/ /opt/docker/

RUN set -x \
    && rpm -Uvh https://mirror.webtatic.com/yum/el7/webtatic-release.rpm \
    && yum-install \
        ImageMagick \
        GraphicsMagick \
        ghostscript \
        jpegoptim \
        libjpeg-turbo-utils \
        optipng \
        pngcrush \
        pngnq \
        pngquant \
        # Install php (cli/fpm)
        php70w-cli \
        php70w-fpm \
        php70w-common \
        php70w-intl \
        php70w-imap \
        php70w-mysqlnd \
        php70w-pecl-memcached \
        php70w-mcrypt \
        php70w-gd \
        php70w-pgsql \
        php70w-mbstring \
        php70w-bcmath \
        php70w-soap \
        php70w-pecl-apcu \
        sqlite \
        php70w-xmlrpc \
        php70w-xml \
        geoip \
        php70w-ldap \
        ImageMagick-devel \
        ImageMagick-perl \
        php70w-pear \
        php70w-devel \
        gcc \
        make \
        php70w-opcache \
        php70w-pecl-imagick \
        php70w-pecl-mongodb \
    && pecl channel-update pecl.php.net \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer2 \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer1 --1 \
    && ln -sf /usr/local/bin/composer2 /usr/local/bin/composer \
    && pecl install redis \
    && echo "extension=redis.so" > /etc/php.d/redis.ini \
    && yum remove -y ImageMagick-devel php70w-devel gcc make \
    # Enable php services
    && docker-service enable syslog \
    && docker-service enable cron \
    && docker-run-bootstrap \
    && docker-image-cleanup

EXPOSE 9000
