#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:alpine-3
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:alpine-3

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=7
LABEL io.webdevops.version=0.50.4

ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

COPY conf/ /opt/docker/

# Install php environment
RUN /usr/local/bin/apk-install \
        # Install tools
        imagemagick \
        graphicsmagick \
        # Install php (cli/fpm)
        php-cli \
        php-fpm \
        php-json \
        php-intl \
        php-curl \
        php-mysql \
        php-mysqli \
        php-pdo_mysql \
        php-pdo_sqlite \
        php-memcached \
        php-mcrypt \
        php-gd \
        php-bcmath \
        php-soap \
        php-sqlite3 \
        php-bz2 \
        php-calendar \
        php-ctype \
        php-pcntl \
        php-posix \
        php-sockets \
        php-sysvmsg \
        php-sysvsem \
        php-sysvshm \
        php-xmlreader \
        php-exif \
        php-ftp \
        php-gettext \
        php-iconv \
        php-zip \
        php-zlib \
        php-shmop \
        php-wddx \
        sqlite \
        php-xmlrpc \
        php-xsl \
        geoip \
        php-ldap \
        php-memcache \
        php-redis \
        php-pear \
        php-phar \
        php-openssl \
    && pear channel-update pear.php.net \
    && pear upgrade-all \
    && pear config-set auto_discover 1 \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-php \
    && /opt/docker/bin/bootstrap.sh


EXPOSE 9000
