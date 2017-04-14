#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/samson-deployment:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM zendesk/samson:latest


###############################################################################
# Bootstrap
###############################################################################

ENV TERM="xterm" \
    LANG="C.UTF-8" \
    LC_ALL="C.UTF-8"

ADD baselayout.tar /

# Init bootstrap
RUN set -x \
    && apt-update \
    && /usr/local/bin/generate-dockerimage-info \
    # Enable non-free
    && sed -ri "s/(deb.*\/debian $(docker-image-info dist-codename) main)/\1 contrib non-free /" -- /etc/apt/sources.list \
    && apt-update \
    # System update
    && /usr/local/bin/apt-upgrade \
    # Base stuff
    && /usr/local/bin/apt-install \
        apt-transport-https \
        ca-certificates \
        locales \
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
    && GOREPLACE_VERSION=0.5.4 \
    && wget -O /usr/local/bin/go-replace https://github.com/webdevops/goreplace/releases/download/$GOREPLACE_VERSION/gr-64-linux \
    && chmod +x /usr/local/bin/go-replace \
    && apt-get purge -y -f --force-yes wget dirmngr \
    && docker-image-cleanup

###############################################################################
# Base
###############################################################################

ENV DOCKER_CONF_HOME=/opt/docker/ \
    LOG_STDOUT="" \
    LOG_STDERR=""

COPY conf/ /opt/docker/

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
    && docker-image-cleanup

# Install packages
RUN chmod +x /opt/docker/bin/* \
    && /usr/local/bin/apt-install \
        supervisor \
        wget \
        curl \
        net-tools \
        gnupg \
    && chmod +s /usr/local/bin/gosu \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-base \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

###############################################################################
# Base-app
###############################################################################

ENV APPLICATION_USER=application \
    APPLICATION_GROUP=application \
    APPLICATION_PATH=/app \
    APPLICATION_UID=1000 \
    APPLICATION_GID=1000

# Install services
RUN /usr/local/bin/apt-install \
        # Install common tools
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
    && /usr/local/bin/generate-locales \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-base-app \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

###############################################################################
# Samson
###############################################################################

ENV RAILS_ENV="production"

ENV SQLITE_CLEANUP_DAYS=0

# NGINX reverse proxy
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
       && echo deb https://apt.dockerproject.org/repo debian-jessie main > /etc/apt/sources.list.d/docker.list \
       && curl -fsSL https://yum.dockerproject.org/gpg | apt-key add - \
    && /usr/local/bin/apt-install \
        # Install nginx
        nginx \
        # Install docker
        docker-engine \
        # Install php
        php5-cli \
        php5-mysqlnd \
        php5-mcrypt \
        php5-curl \
        php5-recode \
        php5-json \
        # Install java
        openjdk-7-jre \
        # SQlite 3
        sqlite3 \
    && pip install -U \
        docker-compose \
        python-dotenv \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    ## Enable ansible for deployment user
    && chmod 755 /usr/local/bin/ansible* \
    && docker-image-cleanup

# NPM stack
RUN curl -sL https://deb.nodesource.com/setup_7.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm \
    && npm install -g gulp \
    && npm install -g grunt-cli \
    && npm install -g bower \
    && npm install -g npm-cache \
    && docker-image-cleanup

# Deployer stack
RUN set -x \
    # Compiler stuff
    && /usr/local/bin/apt-install \
        build-essential \
    # Deployer: Ansistratno (https://github.com/ansistrano)
    && ansible-galaxy install --force \
        carlosbuenosvinos.ansistrano-deploy \
        carlosbuenosvinos.ansistrano-rollback \
    # Deployer: PHP Deployer (http://deployer.org/)
    && wget --quiet -O/usr/local/bin/dep http://deployer.org/deployer.phar \
    && chmod +x /usr/local/bin/dep \
    # Deployer: capistrano (http://capistranorb.com/)
    && gem install capistrano \
    # Deplyer: Magallanes
    && wget http://download.magephp.com/magallanes.latest.tar.gz -O /tmp/magallanes.tar.gz \
    && tar -xzf /tmp/magallanes.tar.gz -C /tmp/ \
    && /tmp/magallanes/bin/mage install --installDir=/opt/magallanes \
    && rm -rf /tmp/magallanes.tar.gz /tmp/magallanes/ \
    && ln -s /opt/magallanes/latest/bin/mage /usr/bin/mage \
    && docker-image-cleanup

# Upload
ADD database.yml    /app/config/database.yml
ADD web/            /app/public/assets/

ADD crontab         /etc/cron.d/webdevops-samson-deployment

RUN rake assets:precompile \
    && /opt/docker/bin/control.sh service.enable cron \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-base --role webdevops-base-app --role webdevops-samson-deployment \
    && /opt/docker/bin/bootstrap.sh

EXPOSE 80
VOLUME /storage

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["supervisord"]
