#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-official:5.6
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM php:5.6-fpm

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.5.0

ENV TERM   "xterm"
ENV LANG   "en_US.UTF-8"
ENV LC_ALL "en_US.UTF-8"
ENV APPLICATION_USER  application
ENV APPLICATION_GROUP application
ENV APPLICATION_PATH  /app
ENV APPLICATION_UID   1000
ENV APPLICATION_GID   1000
ENV LOG_STDOUT ""
ENV LOG_STDERR ""

ADD baselayout.tar /

COPY conf/ /opt/docker/

# Init bootstrap
RUN set -x \
    # Fix root terminal
    && echo "export TERM=xterm" >> /root/.bashrc \
    && apt-update \
    && /usr/local/bin/generate-dockerimage-info \
    # Enable non-free
    && sed -ri "s/(deb.*\/debian $(cat /etc/dockerimage_lsb_codename) main)/\1 contrib non-free /" -- /etc/apt/sources.list \
    && apt-update \
    # System update
    && /usr/local/bin/apt-upgrade \
    # Base stuff
    && /usr/local/bin/apt-install \
        apt-transport-https \
        ca-certificates \
        locales \
    # Generate base locale
    && echo "en_US.UTF-8 UTF-8" > /etc/locale.gen \
    && locale-gen \
    # Install gosu
    && GOSU_VERSION=1.10 \
    && /usr/local/bin/apt-install wget gnupg dirmngr \
    && dpkgArch="amd64" \
    && wget -O /usr/local/bin/gosu "https://github.com/tianon/gosu/releases/download/$GOSU_VERSION/gosu-$dpkgArch" \
    && wget -O /usr/local/bin/gosu.asc "https://github.com/tianon/gosu/releases/download/$GOSU_VERSION/gosu-$dpkgArch.asc" \
    && export GNUPGHOME="$(mktemp -d)" \
    && gpg --keyserver ha.pool.sks-keyservers.net --recv-keys B42F6819007F00F88E364FD4036A9C25BF357DD4 \
    && gpg --batch --verify /usr/local/bin/gosu.asc /usr/local/bin/gosu \
    && rm -rf "$GNUPGHOME" /usr/local/bin/gosu.asc \
    && chmod +x /usr/local/bin/gosu \
    && gosu nobody true \
    ## Install go-replace
    && GOREPLACE_VERSION=0.5.2 \
    && wget -O /usr/local/bin/go-replace https://github.com/webdevops/goreplace/releases/download/$GOREPLACE_VERSION/gr-64-linux \
    && chmod +x /usr/local/bin/go-replace \
    && apt-get purge -y -f --force-yes wget dirmngr \
    && /usr/local/bin/docker-image-cleanup

# Install ansible
RUN /usr/local/bin/apt-install \
        # Install ansible
        python-minimal \
        python-setuptools \
        python-pip \
        python-paramiko \
        python-jinja2 \
        python-dev \
        libffi-dev \
        libssl-dev \
        build-essential \
    && pip install --upgrade pip \
    && hash -r \
    && pip install --no-cache-dir ansible \
    && chmod 750 /usr/local/bin/ansible* \
    # Cleanup
    && apt-get purge -y -f --force-yes \
        python-dev \
        build-essential \
        libssl-dev \
        libffi-dev \
    && /usr/local/bin/docker-image-cleanup

# Install packages
RUN chmod +x /opt/docker/bin/* \
    && /usr/local/bin/apt-install \
        python-minimal \
        supervisor \
        syslog-ng \
        syslog-ng-core \
        wget \
        curl \
        net-tools \
        gnupg \
    && chmod +s /usr/local/bin/gosu \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-base \
    && /opt/docker/bin/bootstrap.sh \
    && /usr/local/bin/docker-image-cleanup

# Install services
RUN /usr/local/bin/apt-install \
        # Install common tools
        sudo \
        zip \
        unzip \
        bzip2 \
        wget \
        curl \
        net-tools \
        moreutils \
        dnsutils \
        openssh-client \
        rsync \
        git \
        nano \
        vim \
    && /usr/local/bin/generate-locales \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-base-app \
    && /opt/docker/bin/bootstrap.sh \
    && /usr/local/bin/docker-image-cleanup

# Install php environment
RUN /usr/local/bin/apt-install \
        # Install tools
        imagemagick \
        graphicsmagick \
        ghostscript \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-php \
    && /opt/docker/bin/bootstrap.sh \
    && /usr/local/bin/docker-image-cleanup

EXPOSE 9000
ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["supervisord"]
