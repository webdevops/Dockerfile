#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base:centos-7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:centos-7

ENV DOCKER_CONF_HOME=/opt/docker/ \
    LOG_STDOUT="" \
    LOG_STDERR=""

COPY conf/ /opt/docker/

# Install ansible
RUN /usr/local/bin/yum-install \
        epel-release \
        PyYAML \
        python-jinja2 \
        python-httplib2 \
        python-keyczar \
        python-paramiko \
        python-setuptools \
        python-setuptools-devel \
        libffi \
        python-devel \
        libffi-devel \
    && easy_install pip \
    && pip install --upgrade pip \
    && hash -r \
    && pip install --no-cache-dir ansible \
    && chmod 750 /usr/bin/ansible* \
    # Cleanup
    && yum erase -y python-devel \
    && docker-image-cleanup

# Install services
RUN chmod +x /opt/docker/bin/* \
    && /usr/local/bin/yum-install \
        supervisor \
        wget \
        curl \
        net-tools \
        gnupg2 \
    && chmod +s /usr/local/bin/gosu \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

ENTRYPOINT ["/entrypoint"]
CMD ["supervisord"]
