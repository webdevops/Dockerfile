#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/samson-deployment:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++


# Staged baselayout builder
FROM webdevops/toolbox AS baselayout
RUN mkdir -p \
        /baselayout/sbin \
        /baselayout/usr/local/bin \
    # Baselayout scripts
    && wget -O /tmp/baselayout-install.sh https://raw.githubusercontent.com/webdevops/Docker-Image-Baselayout/master/install.sh \
    && sh /tmp/baselayout-install.sh /baselayout \
    ## Install go-replace
    && wget -O "/baselayout/usr/local/bin/go-replace" "https://github.com/webdevops/goreplace/releases/download/1.1.2/gr-64-linux" \
    && chmod +x "/baselayout/usr/local/bin/go-replace" \
    && "/baselayout/usr/local/bin/go-replace" --version \
    # Install gosu
    && wget -O "/baselayout/sbin/gosu" "https://github.com/tianon/gosu/releases/download/1.10/gosu-amd64" \
    && wget -O "/tmp/gosu.asc" "https://github.com/tianon/gosu/releases/download/1.10/gosu-amd64.asc" \
    && export GNUPGHOME="$(mktemp -d)" \
    && gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys B42F6819007F00F88E364FD4036A9C25BF357DD4 \
    && gpg --batch --verify /tmp/gosu.asc "/baselayout/sbin/gosu" \
    && rm -rf "$GNUPGHOME" /tmp/gosu.asc \
    && chmod +x "/baselayout/sbin/gosu" \
    && "/baselayout/sbin/gosu" nobody true


FROM zendesk/samson:latest

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

###############################################################################
# Bootstrap
###############################################################################


# Baselayout copy (from staged image)
COPY --from=baselayout /baselayout /


RUN set -x \
    # Init bootstrap
    && apt-update \
    && /usr/local/bin/generate-dockerimage-info \
    # Enable non-free
    && sed -ri "s/(deb.*\/debian $(docker-image-info dist-codename) main)/\1 contrib non-free /" -- /etc/apt/sources.list \
    && apt-update \
    # System update
    && /usr/local/bin/apt-upgrade \
    # Base stuff
    && apt-install \
        apt-transport-https \
        ca-certificates \
        locales \
        gnupg \
    && docker-image-cleanup

###############################################################################
# Base
###############################################################################

COPY conf/ /opt/docker/

RUN set -x \
    # Install ansible
    && apt-install \
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
        openssh-client \
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
    && docker-image-cleanup

RUN set -x \
    # Install packages
    && chmod +x /opt/docker/bin/* \
    && apt-install \
        supervisor \
        wget \
        curl \
        vim \
        net-tools \
        tzdata \
    && chmod +s /sbin/gosu \
    && docker-run-bootstrap \
    && docker-image-cleanup

###############################################################################
# Base-app
###############################################################################

RUN set -x \
    # Install services
    && apt-install \
        # Install common tools
        zip \
        unzip \
        bzip2 \
        moreutils \
        dnsutils \
        openssh-client \
        rsync \
        git \
        patch \
    && /usr/local/bin/generate-locales \
    && docker-run-bootstrap \
    && docker-image-cleanup

###############################################################################
# Samson
###############################################################################

RUN set -x \
    ENV RAILS_ENV="production"

ENV SQLITE_CLEANUP_DAYS=0

# NGINX reverse proxy
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
       && echo deb https://apt.dockerproject.org/repo debian-jessie main > /etc/apt/sources.list.d/docker.list \
       && curl -fsSL https://yum.dockerproject.org/gpg | apt-key add - \
    && apt-install \
        # Install nginx
        nginx \
        # Install docker
        docker-engine \
        # Install php
        php-cli \
        php-mysqlnd \
        php-mcrypt \
        php-curl \
        php-recode \
        php-json \
        # Install java
        openjdk-8-jre \
        # SQlite 3
        sqlite3 \
    && pip install -U \
        docker-compose \
        python-dotenv \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer1 --version=1.10.16 \
    ## Enable ansible for deployment user
    && chmod 755 /usr/local/bin/ansible* \
    && docker-image-cleanup

# NPM stack
RUN curl -sL https://deb.nodesource.com/setup_9.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g gulp \
    && npm install -g grunt-cli \
    && npm install -g bower \
    && npm install -g npm-cache \
    && docker-image-cleanup

# Deployer stack
RUN set -x \
    # Compiler stuff
    && apt-install \
        build-essential \
    # Deployer: Ansistratno (https://github.com/ansistrano)
    && ansible-galaxy install --force \
        ansistrano.deploy \
        ansistrano.rollback \
    # Deployer: PHP Deployer (http://deployer.org/)
    && wget --quiet -O/usr/local/bin/dep http://deployer.org/deployer.phar \
    && chmod +x /usr/local/bin/dep \
    # Deployer: capistrano (http://capistranorb.com/)
    && gem install capistrano \
    && docker-image-cleanup

# Upload
ADD database.yml    /app/config/database.yml
ADD web/            /app/public/assets/

ADD crontab         /etc/cron.d/webdevops-samson-deployment

RUN rake assets:precompile \
    && docker-service enable cron \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-samson-deployment \
    && /opt/docker/bin/bootstrap.sh \
    && docker-run-bootstrap \
    && docker-image-cleanup

EXPOSE 80
VOLUME /storage

ENTRYPOINT ["/entrypoint"]
CMD ["supervisord"]
