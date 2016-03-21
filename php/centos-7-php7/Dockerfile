#++++++++++++++++++++++++++++++++++++++
# CentOS 7 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:centos-7
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=5
LABEL io.webdevops.version=0.20.0



# Install tools
RUN /usr/local/bin/yum-install \
        ImageMagick \
        GraphicsMagick \
        ImageMagick-devel \
        ImageMagick-perl \
        gcc \
        make

# Install instantClient
# Add files from the repo to the image.
# These files were downloaded from:
#
#   Instant Client Downloads for Linux x86-64
#   http://www.oracle.com/technetwork/topics/linuxx86-64soft-092277.html
#
# NOTE: Can't automate the download because an agreement has to checked on the page.
ADD assets /assets
RUN /usr/local/bin/yum-install \
        /assets/oracle-instantclient11.2-basic-11.2.0.4.0-1.x86_64.rpm \
        /assets/oracle-instantclient11.2-devel-11.2.0.4.0-1.x86_64.rpm \
        /assets/oracle-instantclient11.2-sqlplus-11.2.0.4.0-1.x86_64.rpm \
    && rm -rf /assets

# Oracle Settings
ENV ORACLE_HOME /usr/lib/oracle/11.2/client64
ENV LD_LIBRARY_PATH $ORACLE_HOME/lib/:$LD_LIBRARY_PATH
ENV NLS_LANG FRENCH_FRANCE.WE8ISO8859P15
ENV NLS_NUMERIC_CHARACTERS=.
ENV NLS_DATE_FORMAT=YYYYMMDD
ENV TNS_ADMIN=/etc/oracle
ENV ORACLE_SID=ORA11G
ENV PATH $ORACLE_HOME/bin:$PATH
    
# Install php (cli/fpm)
RUN rpm -Uvh https://mirror.webtatic.com/yum/el7/webtatic-release.rpm \
    && /usr/local/bin/yum-install \
        php70w-cli \
        php70w-fpm \
        php70w-common \
        php70w-intl \
        php70w-mysqlnd \
        php70w-pecl-xdebug \
#        php70w-memcached \
        php70w-mcrypt \
        php70w-gd \
        php70w-mbstring \
        php70w-bcmath \
        php70w-soap \
        sqlite \
        php70w-xmlrpc \
        php70w-xml \
        geoip \
        php70w-ldap \
        #php70w-memcache \
        php70w-pear \
        php70w-devel \
        php70w-pdo \
        php70w-odbc \
        php70w-pdo_dblib \
        php70w-process \
        php70w-pecl-imagick \
        php70w-opcache \ 
    && pear channel-update pear.php.net \
    && pear config-set auto_discover 1 \
    && printf "instantclient,/usr/lib/oracle/11.2/client64/lib/\n" | pecl install oci8 \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-php \
    && bash /opt/docker/bin/bootstrap.sh



EXPOSE 9000

CMD ["supervisord"]
