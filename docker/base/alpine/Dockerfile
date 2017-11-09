#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base:alpine
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:alpine

ENV DOCKER_CONF_HOME=/opt/docker/ \
    LOG_STDOUT="" \
    LOG_STDERR=""

COPY conf/ /opt/docker/

RUN set -x \
    # Install services
    && chmod +x /opt/docker/bin/* \
    && apk-install \
        supervisor \
        wget \
        curl \
        sed \
        tzdata \
    && chmod +s /sbin/gosu \
    && docker-run-bootstrap \
    && docker-image-cleanup

ENTRYPOINT ["/entrypoint"]
CMD ["supervisord"]
