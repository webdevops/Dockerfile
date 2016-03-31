#++++++++++++++++++++++++++++++++++++++
# CentOS 7 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:alpine-3
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=5
LABEL io.webdevops.version=0.21.0

# Install tools
RUN /usr/local/bin/apk-install \
    imagemagick \
    graphicsmagick

# Install php (cli/fpm)
RUN /usr/local/bin/apk-install \
        php-cli \
        php-fpm \
        php-json \
        php-intl \
        php-curl \
        php-mysql \
        php-mysqli \
        php-pdo_mysql \
        php-pdo_sqlite \
        php-xdebug \
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
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-php \
    && bash /opt/docker/bin/bootstrap.sh



EXPOSE 9000

CMD ["supervisord"]
