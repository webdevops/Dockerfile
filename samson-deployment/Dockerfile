FROM zendesk/samson
MAINTAINER info@webdevops.io

ENV PATH             /opt/ansible/bin:/bin:/usr/bin:/sbin:/usr/sbin:/usr/local/bin/:/app/bin/:/opt/deployment
ENV PYTHONPATH       /opt/ansible/lib
ENV ANSIBLE_LIBRARY  /opt/ansible/library

# Ansible
RUN set -x \
    && apt-get update \
    && apt-get install -y \
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
        rsync \
        supervisor \
    && pip install ansible \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Ansistratno
RUN ansible-galaxy install --force \
    carlosbuenosvinos.ansistrano-deploy \
    carlosbuenosvinos.ansistrano-rollback

# Docker (client)
RUN set -x \
    && echo deb https://apt.dockerproject.org/repo debian-jessie main > /etc/apt/sources.list.d/docker.list \
    && apt-key adv --keyserver hkp://pgp.mit.edu:80 --recv-keys 58118E89F3A912897C070ADBF76221572C52609D \
    && apt-get update \
    && apt-get install -y \
        docker-engine \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Docker-compose
RUN pip install docker-compose

# NPM stack
RUN set -x \
    && npm install -g gulp \
    && npm install -g grunt-cli \
    && npm install -g bower

# PHP stack
RUN set -x \
    && apt-get update \
    && apt-get install -y \
        php5-cli \
        php5-mysqlnd \
        php5-mcrypt \
        php5-curl \
        php5-recode \
        php5-json \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Update
RUN set -x \
    && apt-get update \
    && apt-get dist-upgrade -y \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Run
RUN mkdir -p /opt/docker
COPY bin   /opt/docker/bin/
COPY conf  /opt/docker/conf/

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["supervisord"]
