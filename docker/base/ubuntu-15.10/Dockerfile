#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base:ubuntu-15.10
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:ubuntu-15.10

ENV DOCKER_CONF_HOME=/opt/docker/ \
    LOG_STDOUT="" \
    LOG_STDERR=""

COPY conf/ /opt/docker/

# Install ansible
RUN /usr/local/bin/apt-install \
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

# Install services
RUN chmod +x /opt/docker/bin/* \
    && /usr/local/bin/apt-install \
        supervisor \
        wget \
        curl \
        net-tools \
        gnupg \
    && chmod +s /usr/local/bin/gosu \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

ENTRYPOINT ["/entrypoint"]
CMD ["supervisord"]
