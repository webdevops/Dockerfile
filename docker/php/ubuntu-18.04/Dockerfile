#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:ubuntu-18.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:ubuntu-18.04

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
        php7.2-cli \
        php7.2-fpm \
        php7.2-json \
        php7.2-intl \
        php7.2-curl \
        php7.2-mysql \
        php7.2-gd \
        php7.2-sqlite3 \
        php7.2-imap \
        php7.2-pgsql \
        php7.2-ldap \
        php7.2-opcache \
        php7.2-soap \
        php7.2-zip \
        php7.2-mbstring \
        php7.2-bcmath \
        php7.2-xmlrpc \
        php7.2-xsl \
        php7.2-bz2 \
        php-pear \
        php-apcu \
        php-igbinary \
        php-mongodb \
        php-imagick \
        php-redis \
        php-amqp \
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
