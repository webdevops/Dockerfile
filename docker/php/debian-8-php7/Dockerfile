#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php:debian-8-php7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:debian-8

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm

COPY conf/ /opt/docker/

RUN /usr/local/bin/apt-install apt-transport-https lsb-release \
    && echo "deb https://packages.sury.org/php/ jessie main" >> /etc/apt/sources.list \
    && echo "deb http://ftp2.de.debian.org/debian/ testing main" >> /etc/apt/sources.list \
    && echo "deb-src http://ftp2.de.debian.org/debian/ testing main" >> /etc/apt/sources.list \
    && wget -O- https://packages.sury.org/php/apt.gpg | apt-key add - \
    && echo "Package: *" > /etc/apt/preferences.d/debian_testing.pref \
    && echo "Pin: origin ftp2.de.debian.org" >> /etc/apt/preferences.d/debian_testing.pref \
    && echo "Pin-Priority: -10" >> /etc/apt/preferences.d/debian_testing.pref \
    && echo "Package: libpcre3" > /etc/apt/preferences.d/libpcre.pref \
    && echo "Pin: release a=testing" >> /etc/apt/preferences.d/libpcre.pref \
    && echo "Pin-Priority: 995" >> /etc/apt/preferences.d/libpcre.pref \
    && apt-get update \
    && apt-get -t testing install -y -f libpcre3 \
    && /usr/local/bin/apt-install \
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
        php7.0-xmlrpc \
        php7.0-xsl \
        php7.0-bz2 \
        php7.0-redis \
        php7.0-memcached \
        php7.0-zip \
        php7.0-soap \
        php7.0-bcmath \
        php7.0-mbstring \
        php-apcu \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    # Enable php services
    && docker-service-enable syslog cron \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 9000
