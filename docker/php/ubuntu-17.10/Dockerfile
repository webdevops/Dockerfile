#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:ubuntu-17.10
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:ubuntu-17.10

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
        jpegoptim \
        libjpeg-turbo-progs \
        pngcrush \
        optipng \
        apngopt \
        pngnq \
        pngquant \
        # Install php (cli/fpm)
        php7.1-cli \
        php7.1-fpm \
        php7.1-json \
        php7.1-intl \
        php7.1-curl \
        php7.1-mysql \
        php7.1-gd \
        php7.1-sqlite3 \
        php7.1-imap \
        php7.1-pgsql \
        php7.1-ldap \
        php7.1-opcache \
        php7.1-soap \
        php7.1-zip \
        php7.1-mbstring \
        php7.1-bcmath \
        php7.1-xmlrpc \
        php7.1-xsl \
        php7.1-bz2 \
        php-pear \
        php-apcu \
        php-igbinary \
        php-mongodb \
        php-imagick \
        php-redis \
        php-amqp \
        php-libsodium \
        php-memcached \
    && pecl channel-update pecl.php.net \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer2 \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer1 --1 \
    && ln -sf /usr/local/bin/composer2 /usr/local/bin/composer \
    # Enable php services
    && docker-service enable syslog \
    && docker-service enable cron \
    && docker-run-bootstrap \
    && docker-image-cleanup

EXPOSE 9000
