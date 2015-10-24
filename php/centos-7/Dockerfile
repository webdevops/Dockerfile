#++++++++++++++++++++++++++++++++++++++
# CentOS 7 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:centos-7
MAINTAINER info@webdevops.io

# Install tools
RUN yum -y install \
        ImageMagick \
        GraphicsMagick \
    && yum clean all
    
# Install php (cli/fpm)
RUN yum -y install \
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
    && pecl install imagick \
    && sed -i '/listen =/c\listen = 0.0.0.0:9000' /etc/php-fpm.d/www.conf \
    && sed -i '/listen.allowed_clients = /c\;listen.allowed_clients = 0.0.0.0' /etc/php-fpm.d/www.conf \
    && echo 'catch_workers_output = yes'                          >> /etc/php-fpm.d/www.conf \
    && echo 'access.log = /var/log/php.access.log'                >> /etc/php-fpm.d/www.conf \
    && echo 'slowlog = /var/log/php.slow.log'                     >> /etc/php-fpm.d/www.conf \
    && echo 'php_admin_value[error_log] = /var/log/php.error.log' >> /etc/php-fpm.d/www.conf \
    && echo 'php_admin_value[log_errors] = on'                    >> /etc/php-fpm.d/www.conf \
    && yum clean all

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/bootstrap.sh

# Configure volume/workdir
RUN mkdir -p /docker/code/
VOLUME /docker/
WORKDIR /docker/code/

EXPOSE 9000

CMD ["supervisord"]