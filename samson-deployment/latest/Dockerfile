FROM zendesk/samson
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=4
LABEL io.webdevops.version=0.19.0

###############################################################################
# Bootstrap
###############################################################################

# Ensure UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# Add common scripts (from _localscripts)
ADD scripts.tar /

# Init bootstrap (and install ansible)
RUN export DEBIAN_FRONTEND=noninteractive \
    && set -x \
    # Fix root terminal
    && echo "export TERM=xterm" >> /root/.bashrc \
    # System update
    && /usr/local/bin/apt-upgrade \
    # Base stuff
    && /usr/local/bin/apt-install \
        lsb-release \
        # Generate locales
        locales-all \
    # Install ansible
        python-dev \
        python-pip \
    && pip install ansible

###############################################################################
# Base
###############################################################################

ENV APPLICATION_USER  application
ENV APPLICATION_GROUP application
ENV APPLICATION_UID   1000
ENV APPLICATION_GID   1000
ENV DOCKER_CONF_HOME  /opt/docker/

# Install services
RUN sed -ri 's/(deb.*\/debian jessie main)/\1 contrib non-free /' -- /etc/apt/sources.list \
    && /usr/local/bin/apt-install \
        supervisor \
        syslog-ng \
        syslog-ng-core \
        logrotate \
        openssh-server \
        mysql-client \
        cron \
        dnsmasq \
        postfix

# Install common tools
RUN /usr/local/bin/apt-install \
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
        vim

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role webdevops-base \
    && bash /opt/docker/bin/control.sh provision.role.finish webdevops-cleanup \
    && bash /opt/docker/bin/bootstrap.sh

###############################################################################
# Samson
###############################################################################

# Ansible
RUN /usr/local/bin/apt-install \
        python-crypto \
        python-httplib2 \
        python-jinja2 \
        python-markupsafe \
        python-paramiko \
        python-selinux \
        python-yaml \
        python-pip \
        sshpass \
        git \
        rsync

# Ansistratno (https://github.com/ansistrano)
RUN ansible-galaxy install --force \
    carlosbuenosvinos.ansistrano-deploy \
    carlosbuenosvinos.ansistrano-rollback

# PHP Deployer (http://deployer.org/)
RUN wget --quiet -O/usr/local/bin/dep http://deployer.org/deployer.phar \
    && chmod +x /usr/local/bin/dep

# Docker (client)
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
    && echo deb https://apt.dockerproject.org/repo debian-jessie main > /etc/apt/sources.list.d/docker.list \
    && apt-key adv --keyserver hkp://pgp.mit.edu:80 --recv-keys 58118E89F3A912897C070ADBF76221572C52609D \
    && /usr/local/bin/apt-install \
        docker-engine \
    && pip install docker-compose

# NPM stack
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
    && npm install -g gulp \
    && npm install -g grunt-cli \
    && npm install -g bower

# PHP stack
RUN /usr/local/bin/apt-install \
        php5-cli \
        php5-mysqlnd \
        php5-mcrypt \
        php5-curl \
        php5-recode \
        php5-json \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

# NGINX reverse proxy
RUN /usr/local/bin/apt-install \
        nginx

# Precompile assets
ADD database.yml    /app/config/database.yml

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role webdevops-samson-deployment \ \
    && bash /opt/docker/bin/bootstrap.sh

RUN rake assets:precompile

EXPOSE 80 9080

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["supervisord"]
