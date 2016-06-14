#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/base:ubuntu-16.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:ubuntu-16.04

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=7
LABEL io.webdevops.version=0.50.6

ENV DOCKER_CONF_HOME /opt/docker/

COPY conf/ /opt/docker/

# Install services
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
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-base \
    && /opt/docker/bin/bootstrap.sh


ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["supervisord"]
