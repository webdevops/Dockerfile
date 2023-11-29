#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-official:8.0-alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++


FROM php:8.0-fpm-alpine

LABEL maintainer=info@webdevops.io \
      vendor=WebDevOps.io \
      io.webdevops.layout=8 \
      io.webdevops.version=1.5.0

ENV TERM="xterm" \
    LANG="C.UTF-8" \
    LC_ALL="C.UTF-8"
ENV DOCKER_CONF_HOME=/opt/docker/ \
    LOG_STDOUT="" \
    LOG_STDERR=""
ENV APPLICATION_USER=application \
    APPLICATION_GROUP=application \
    APPLICATION_PATH=/app \
    APPLICATION_UID=1000 \
    APPLICATION_GID=1000
ENV PHP_SENDMAIL_PATH="/usr/sbin/sendmail -t -i" 
ENV LD_PRELOAD="/usr/lib/preloadable_libiconv.so"
ENV COMPOSER_VERSION="2"


# Baselayout copy (from staged image)
COPY --from=webdevops/toolbox /baselayout/sbin/* /sbin/
COPY --from=webdevops/toolbox /baselayout/usr/local/bin/* /usr/local/bin/


COPY conf/ /opt/docker/

RUN set -x \
    # Init bootstrap
    # Add community
    && echo https://dl-4.alpinelinux.org/alpine/v3.16/community/ >> /etc/apk/repositories \
    # System update
    && /usr/local/bin/apk-upgrade \
    # Install base stuff
    && apk-install \
        bash \
        ca-certificates \
        openssl \
    && update-ca-certificates \
    && /usr/local/bin/generate-dockerimage-info \
    ## Fix su execution (eg for tests)
    && mkdir -p /etc/pam.d/ \
    && echo 'auth sufficient pam_rootok.so' >> /etc/pam.d/su

RUN set -x \
    # Install services
    && chmod +x /opt/docker/bin/* \
    && apk-install \
        supervisor \
        wget \
        curl \
        vim \
        sed \
        tzdata \
        busybox-suid \
    && chmod +s /sbin/gosu \
    && docker-run-bootstrap \
    && docker-image-cleanup

RUN set -x \
    && apk-install shadow \
    && apk-install \
        # Install common tools
        zip \
        unzip \
        bzip2 \
        drill \
        ldns \
        openssh-client \
        rsync \
        patch \
        git \
    && docker-run-bootstrap \
    && docker-image-cleanup

RUN set -x \
    # Install php environment
    && apk-install \
        imagemagick \
        graphicsmagick \
        ghostscript \
        jpegoptim \
        pngcrush \
        optipng \
        pngquant \
        vips \
        rabbitmq-c \
        c-client \
        # Libraries
        libldap \
        icu-libs \
        libintl \
        libpq \
        libxslt \
        libgomp \
        libzip \
        libmemcached \
        yaml \
        # Build dependencies
        linux-headers \
        autoconf \
        g++ \
        make \
        libtool \
        pcre-dev \
        gettext-dev \
        freetype-dev \
        gmp-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        vips-dev \
        krb5-dev \
        openssl-dev \
        imap-dev \
        imagemagick-dev \
        rabbitmq-c-dev \
        openldap-dev \
        icu-dev \
        postgresql-dev \
        libxml2-dev \
        ldb-dev \
        pcre-dev \
        libxslt-dev \
        libzip-dev \
        libmemcached-dev \
        yaml-dev \
    # Install guetzli
    && wget https://github.com/google/guetzli/archive/master.zip \
    && unzip master.zip \
    && make -C guetzli-master \
    && cp guetzli-master/bin/Release/guetzli /usr/local/bin/ \
    && rm -rf master.zip guetzli-master \
    # https://github.com/docker-library/php/issues/240
    && apk add gnu-libiconv --update-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/testing/ --allow-untrusted \
    # Install new version of ICU
    && curl -sS -o /tmp/icu.tar.gz -L https://github.com/unicode-org/icu/releases/download/release-73-1/icu4c-73_1-src.tgz \
    && tar -zxf /tmp/icu.tar.gz -C /tmp && cd /tmp/icu/source && ./configure --prefix=/usr/local && make && make install && cd / && rm -rf /tmp/icu* \
    # Install extensions
    && PKG_CONFIG_PATH=/usr/local docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
    && git clone --branch master --depth 1 https://github.com/Imagick/imagick.git /usr/src/php/ext/imagick \
    && git clone --branch latest --depth 1 https://github.com/php-amqp/php-amqp.git /usr/src/php/ext/amqp \
    && cd /usr/src/php/ext/amqp && git submodule update --init \
    && docker-php-ext-configure ldap \
    && PHP_OPENSSL=yes docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install \
        bcmath \
        bz2 \
        calendar \
        exif \
        gmp \
        ffi \
        imagick \
        amqp \
        intl \
        gettext \
        ldap \
        mysqli \
        imap \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        soap \
        sockets \
        tokenizer \
        sysvmsg \
        sysvsem \
        sysvshm \
        shmop \
        xsl \
        zip \
        gd \
        gettext \
        opcache \
    # Install extensions for PHP 7.x
    # Memcached for 7.3 can currently only be built from master
    && MEMCACHED="`mktemp -d`" \
    && curl -skL https://github.com/php-memcached-dev/php-memcached/archive/master.tar.gz | tar zxf - --strip-components 1 -C $MEMCACHED \
    && docker-php-ext-configure $MEMCACHED \
    && docker-php-ext-install $MEMCACHED \
    && rm -rf $MEMCACHED \
    && pecl install apcu \
    && pecl install vips \
    && pecl install yaml \
    && pecl install redis \
    && pecl install mongodb \
    && pecl install xmlrpc-1.0.0RC3 \
    && docker-php-ext-enable \
        apcu \
        vips \
        yaml \
        redis \
        xmlrpc \
        imagick \
        mongodb \
    # Uninstall dev and header packages
    && apk del -f --purge \
        autoconf \
        linux-headers \
        g++ \
        make \
        libtool \
        pcre-dev \
        gettext-dev \
        freetype-dev \
        gmp-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        vips-dev \
        krb5-dev \
        openssl-dev \
        imap-dev \
        rabbitmq-c-dev \
        imagemagick-dev \
        openldap-dev \
        icu-dev \
        postgresql-dev \
        libxml2-dev \
        ldb-dev \
        pcre-dev \
        libxslt-dev \
        libzip-dev \
        libmemcached-dev \
        yaml-dev \
    && rm -f /usr/local/etc/php-fpm.d/zz-docker.conf \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer2 \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer1 --1 \
    && ln -sf /usr/local/bin/composer2 /usr/local/bin/composer \
    # Enable php services
    && docker-service enable syslog \
    && docker-service enable cron \
    && docker-run-bootstrap \
    && docker-image-cleanup

WORKDIR /
EXPOSE 9000
ENTRYPOINT ["/entrypoint"]
CMD ["supervisord"]
