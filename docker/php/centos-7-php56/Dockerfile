#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:centos-7-php56
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
        php56w-cli \
        php56w-fpm \
        php56w-common \
        php56w-intl \
        php56w-imap \
        php56w-mysqlnd \
        php56w-pecl-memcached \
        php56w-mongodb \
        php56w-mcrypt \
        php56w-gd \
        php56w-pgsql \
        php56w-mbstring \
        php56w-bcmath \
        php56w-soap \
        sqlite \
        php56w-xmlrpc \
        php56w-xml \
        geoip \
        php56w-ldap \
        ImageMagick-devel \
        ImageMagick-perl \
        php56w-pear \
        php56w-devel \
        gcc \
        make \
        php56w-opcache \
    # Temporarily disable pear due to https://twitter.com/pear/status/1086634389465956352
    # && pear channel-update pear.php.net \
    # && pear upgrade-all \
    && pear config-set auto_discover 1 \
    && sed -i "$ s|\-n||g" /usr/bin/pecl \
    && pecl install imagick \
    # && pecl install redis \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer2 \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer1 --1 \
    && ln -sf /usr/local/bin/composer2 /usr/local/bin/composer \
    # Cleanup
    && yum erase -y php-devel gcc \
    # Enable php services
    && docker-service enable syslog \
    && docker-service enable cron \
    && docker-run-bootstrap \
    && docker-image-cleanup

EXPOSE 9000
