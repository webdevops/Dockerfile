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
    && yum clean all

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-php \
    && bash /opt/docker/bin/bootstrap.sh

# Configure volume/workdir
RUN mkdir -p /docker/code/
VOLUME /docker/
WORKDIR /docker/code/

EXPOSE 9000

CMD ["supervisord"]
