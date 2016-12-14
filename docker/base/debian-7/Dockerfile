#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base:debian-7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:debian-7

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.1.0

ENV DOCKER_CONF_HOME /opt/docker/

COPY conf/ /opt/docker/

# Install packages
RUN chmod +x /opt/docker/bin/* \
    && /usr/local/bin/apt-install \
        supervisor \
        syslog-ng \
        syslog-ng-core \
        logrotate \
        cron \
        wget \
        curl \
        net-tools \
        gnupg \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-base \
    && /opt/docker/bin/bootstrap.sh


ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["supervisord"]
