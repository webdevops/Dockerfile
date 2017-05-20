#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base:ubuntu-15.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:ubuntu-15.04

ENV DOCKER_CONF_HOME=/opt/docker/ \
    LOG_STDOUT="" \
    LOG_STDERR=""

COPY conf/ /opt/docker/

# Install services
RUN chmod +x /opt/docker/bin/* \
    && /usr/local/bin/apt-install \
        supervisor \
        wget \
        curl \
        net-tools \
    && chmod +s /usr/local/bin/gosu \
    ## Install go-logd
    && GOLOGD_VERSION=1.0.0 \
    && wget -O /usr/local/bin/go-crond https://github.com/webdevops/go-logd/releases/download/$GOLOGD_VERSION/go-logd-64-linux \
    && chmod +x /usr/local/bin/go-logd \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

ENTRYPOINT ["/entrypoint"]
CMD ["supervisord"]
