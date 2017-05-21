#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base:alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:alpine

ENV DOCKER_CONF_HOME=/opt/docker/ \
    LOG_STDOUT="" \
    LOG_STDERR=""

COPY conf/ /opt/docker/

# Install services
RUN chmod +x /opt/docker/bin/* \
    && /usr/local/bin/apk-install \
        supervisor \
        wget \
        curl \
        sed \
    && chmod +s /usr/local/bin/gosu \
    ## Install go-syslogd
    && GOSYSLOGD_VERSION=0.1.0 \
    && wget -O /sbin/go-syslogd https://github.com/webdevops/go-logd/releases/download/$GOSYSLOGD_VERSION/go-syslogd-64-linux \
    && chmod +x /sbin/go-syslogd \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

ENTRYPOINT ["/entrypoint"]
CMD ["supervisord"]
