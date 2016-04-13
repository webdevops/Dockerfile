#++++++++++++++++++++++++++++++++++++++
# CentOS 7 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:centos-7
MAINTAINER guillaume.camus@gmail.com
LABEL vendor=infogene \
	version=1.0

# Install tools
RUN /usr/local/bin/yum-install \
        ImageMagick \
        GraphicsMagick \
    && rpm -Uvh https://mirror.webtatic.com/yum/el7/webtatic-release.rpm

# Install php (cli/fpm)
RUN /usr/local/bin/yum-install \
        php56w-cli \
        php56w-fpm \
        php56w-common \
        # php-json \ included in php56w-common
        php56w-intl \
        # php-curl \ included in php56w-common
        php56w-mysqlnd \
        php56w-pecl-xdebug \
        php56w-pecl-memcached \
        php56w-mcrypt \
        php56w-gd \
        php56w-mbstring \
        php56w-bcmath \
        php56w-soap \
        sqlite \
        php56w-xmlrpc \
        # php-xsl \ replace by php56w-xml
        php56w-xml \
        geoip \
        php56w-ldap \
        #php-memcache \
        #php-pecl-redis \
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
    && pecl install imagick \
    && pecl install redis \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-php \
    && bash /opt/docker/bin/bootstrap.sh



EXPOSE 9000

CMD ["supervisord"]
