#++++++++++++++++++++++++++++++++++++++
# CentOS 7 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:centos-7
MAINTAINER info@webdevops.io

# Install tools
RUN /usr/local/bin/yum-install \
        ImageMagick \
        GraphicsMagick
    
# Install php (cli/fpm)
RUN /usr/local/bin/yum-install \
        php-cli \
        php-fpm \
        php-json \
        php-intl \
        php-curl \
        php-mysqlnd \
        php-xdebug \
        php-memcached \
        php-mcrypt \
        php-gd \
        sqlite \
        php-xmlrpc \
        php-xsl \
        geoip \
        php-ldap \
        php-memcache \
        ImageMagick \
        ImageMagick-devel \
        ImageMagick-perl \
        php-pear \
        php-devel \
        gcc \
        php-pear \
    && pear channel-update pear.php.net \
    && pear upgrade-all \
    && pear config-set auto_discover 1 \
    && pecl install imagick

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-php \
    && bash /opt/docker/bin/bootstrap.sh



EXPOSE 9000

CMD ["supervisord"]
