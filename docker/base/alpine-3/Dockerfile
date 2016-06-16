#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base:alpine-3
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:alpine-3

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=7
LABEL io.webdevops.version=0.51.0

ENV DOCKER_CONF_HOME /opt/docker/

COPY conf/ /opt/docker/

# Install services
RUN chmod +x /opt/docker/bin/* \
    && /usr/local/bin/apk-install \
        supervisor \
        syslog-ng \
        logrotate \
        wget \
        curl \
        sed \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-base \
    && /opt/docker/bin/bootstrap.sh


ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["supervisord"]
