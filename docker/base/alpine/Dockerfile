#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base:alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:alpine

ENV DOCKER_CONF_HOME=/opt/docker/ \
    LOG_STDOUT="" \
    LOG_STDERR=""

COPY conf/ /opt/docker/

# Install ansible
RUN /usr/local/bin/apk-install \
        python \
        python-dev \
        py-setuptools \
        py-crypto \
        py2-pip \
        py-cparser \
        py-cryptography \
        py-markupsafe \
        py-cffi \
        py-yaml \
        py-jinja2 \
        py-paramiko \
    && pip install --upgrade pip \
    && hash -r \
    && pip install --no-cache-dir ansible \
    && chmod 750 /usr/bin/ansible* \
    # Cleanup
    && apk del python-dev \
    && docker-image-cleanup

# Install services
RUN chmod +x /opt/docker/bin/* \
    && /usr/local/bin/apk-install \
        supervisor \
        wget \
        curl \
        sed \
        gnupg \
    && chmod +s /usr/local/bin/gosu \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

ENTRYPOINT ["/entrypoint"]
CMD ["supervisord"]
