#++++++++++++++++++++++++++++++++++++++
# Ubuntu 15.10 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:ubuntu-15.10
MAINTAINER info@webdevops.io

# Ensure UTF-8
RUN locale-gen en_US.UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# Install tools
RUN set -x \
    && apt-get update \
    && apt-get install -y \
        imagemagick \
        graphicsmagick \
        mysql-client \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Install php
RUN set -x \
    && apt-get update \
    && apt-get install -y \
        php5-cli \
        php5-fpm \
        php5-json \
        php5-intl \
        php5-curl \
        php5-mysqlnd \
        php5-xdebug \
        php5-memcached \
        php5-mcrypt \
        php5-gd \
        php5-sqlite \
        php5-xmlrpc \
        php5-xsl \
        php5-geoip \
        php5-ldap \
        php5-memcache \
        php5-memcached \
        php5-imagick \
    && ln -sf /etc/php5/mods-available/mcrypt.in /etc/php5/cli/conf.d/20-mcrypt.ini \
    && ln -sf /etc/php5/mods-available/mcrypt.in /etc/php5/fpm/conf.d/20-mcrypt.ini \
    && sed -i '/listen = /c\listen = 0.0.0.0:9000' /etc/php5/fpm/pool.d/www.conf \
    && sed -i '/listen.allowed_clients = /c\listen.allowed_clients = 0.0.0.0' /etc/php5/fpm/pool.d/www.conf \
    && echo 'catch_workers_output = yes'                          >> /etc/php5/fpm/pool.d/www.conf \
    && echo 'access.log = /var/log/php.access.log'                >> /etc/php5/fpm/pool.d/www.conf \
    && echo 'slowlog = /var/log/php.slow.log'                     >> /etc/php5/fpm/pool.d/www.conf \
    && echo 'php_admin_value[error_log] = /var/log/php.error.log' >> /etc/php5/fpm/pool.d/www.conf \
    && echo 'php_admin_value[log_errors] = on'                    >> /etc/php5/fpm/pool.d/www.conf \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Deploy scripts/configurations
COPY bin /opt/docker/bin/
COPY etc /opt/docker/etc/

# Configure volume/workdir
RUN mkdir -p /docker/code/
VOLUME /docker/
WORKDIR /docker/code/

EXPOSE 9000

CMD ["supervisord"]
