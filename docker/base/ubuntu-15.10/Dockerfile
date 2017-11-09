#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base:ubuntu-15.10
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:ubuntu-15.10

ENV DOCKER_CONF_HOME=/opt/docker/ \
    LOG_STDOUT="" \
    LOG_STDERR=""

COPY conf/ /opt/docker/

RUN set -x \
    # Install services
    && chmod +x /opt/docker/bin/* \
    && apt-install \
        supervisor \
        wget \
        curl \
        net-tools \
        tzdata \
    && chmod +s /sbin/gosu \
    && docker-run-bootstrap \
    && docker-image-cleanup

ENTRYPOINT ["/entrypoint"]
CMD ["supervisord"]
