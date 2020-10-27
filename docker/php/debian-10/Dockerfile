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
        # Install php (cli/fpm) | php always references the latest version
        php-cli \
        php-fpm \
        php-json \
        php-intl \
        php-curl \
        php-mysql \
        php-gd \
        php-imagick \
        php-imap \
        php-sqlite3 \
        php-pgsql \
        php-ldap \
        php-opcache \
        php-soap \
        php-zip \
        php-mbstring \
        php-bcmath \
        php-xmlrpc \
        php-xsl \
        php-bz2 \
        php-pear \
        php-apcu \
        php-redis \
        php-mongodb \
        php-memcache \
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
