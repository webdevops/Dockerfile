#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base:debian-8
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:debian-8

ENV DOCKER_CONF_HOME=/opt/docker/ \
    LOG_STDOUT="" \
    LOG_STDERR=""

COPY conf/ /opt/docker/

RUN set -x \
    # Install packages
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
