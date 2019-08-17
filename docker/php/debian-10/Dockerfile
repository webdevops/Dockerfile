#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:debian-10
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:debian-10

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm \
    WEB_PHP_TIMEOUT=600 \
    WEB_PHP_SOCKET=""

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
        php7.3-cli \
        php7.3-fpm \
        php7.3-json \
        php7.3-intl \
        php7.3-curl \
        php7.3-mysql \
        php7.3-mcrypt \
        php7.3-gd \
        php7.3-imagick \
        php7.3-imap \
        php7.3-sqlite3 \
        php7.3-pgsql \
        php7.3-ldap \
        php7.3-opcache \
        php7.3-soap \
        php7.3-zip \
        php7.3-mbstring \
        php7.3-bcmath \
        php7.3-xmlrpc \
        php7.3-xsl \
        php7.3-bz2 \
        php-pear \
        php-apcu \
        php-redis \
        php-mongodb \
        php-memcache \
        php-memcached \
    && pecl channel-update pecl.php.net \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    # Enable php services
    && docker-service enable syslog \
    && docker-service enable cron \
    && docker-run-bootstrap \
    && docker-image-cleanup

EXPOSE 9000
