#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:centos-7-php56
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:centos-7

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.5.0

ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

COPY conf/ /opt/docker/

RUN rpm -Uvh https://mirror.webtatic.com/yum/el7/webtatic-release.rpm \
    && /usr/local/bin/yum-install \
        ImageMagick \
        GraphicsMagick \
        ghostscript \
        php56w-cli \
        php56w-fpm \
        php56w-common \
        php56w-intl \
        php56w-mysqlnd \
        php56w-pecl-memcached \
        php56w-mcrypt \
        php56w-gd \
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
    && pear channel-update pear.php.net \
    && pear upgrade-all \
    && pear config-set auto_discover 1 \
    && sed -i "$ s|\-n||g" /usr/bin/pecl \
    && pecl install imagick \
    # && pecl install redis \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    # Cleanup
    && yum erase -y php-devel gcc \
    # Enable php services
    && /opt/docker/bin/control.sh service.enable cron \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-php \
    && /opt/docker/bin/bootstrap.sh \
    && /usr/local/bin/docker-image-cleanup

EXPOSE 9000
