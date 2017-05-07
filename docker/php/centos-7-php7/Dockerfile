#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:centos-7-php7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:centos-7

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm

COPY conf/ /opt/docker/

RUN rpm -Uvh https://mirror.webtatic.com/yum/el7/webtatic-release.rpm \
    && /usr/local/bin/yum-install \
        ImageMagick \
        GraphicsMagick \
        # Install php (cli/fpm)
        php70w-cli \
        php70w-fpm \
        php70w-common \
        php70w-intl \
        php70w-mysqlnd \
        php70w-pecl-memcached \
        php70w-mcrypt \
        php70w-gd \
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
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    && cd /tmp \
    && wget https://github.com/phpredis/phpredis/archive/php7.zip -O phpredis.zip \
    && unzip -o /tmp/phpredis.zip \
    && mv /tmp/phpredis-* /tmp/phpredis \
    && cd /tmp/phpredis \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && cd / \
    && rm -rf /tmp/* \
    && echo "extension=redis.so" > /etc/php.d/redis.ini \
    && yum remove -y ImageMagick-devel php70w-devel gcc make \
    # Enable php services
    && docker-service-enable syslog cron \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 9000
