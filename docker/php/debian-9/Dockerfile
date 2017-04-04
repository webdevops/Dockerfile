#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:debian-9
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:debian-9

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.5.0

ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

COPY conf/ /opt/docker/

# Install php environment
RUN /usr/local/bin/apt-install \
        # Install tools
        imagemagick \
        graphicsmagick \
        ghostscript \
        # Install php (cli/fpm)
        php7.0-cli \
        php7.0-fpm \
        php7.0-json \
        php7.0-intl \
        php7.0-curl \
        php7.0-mysql \
        php7.0-mcrypt \
        php7.0-gd \
        php7.0-sqlite3 \
        php7.0-ldap \
        php7.0-opcache \
        php7.0-soap \
        php7.0-zip \
        php7.0-mbstring \
        php7.0-bcmath \
        php7.0-xmlrpc \
        php7.0-xsl \
        php7.0-bz2 \
        php-apcu \
        php-redis \
        php-memcache \
        php-memcached \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    # Enable php services
    && /opt/docker/bin/control.sh service.enable cron \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-php \
    && /opt/docker/bin/bootstrap.sh \
    && /usr/local/bin/docker-image-cleanup

EXPOSE 9000
