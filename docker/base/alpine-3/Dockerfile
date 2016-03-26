#++++++++++++++++++++++++++++++++++++++
# Alpine 3 Bootstrap Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:alpine-3
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=5
LABEL io.webdevops.version=0.20.0

ENV DOCKER_CONF_HOME  /opt/docker/

# Install services
RUN /usr/local/bin/apk-install \
        supervisor \
        syslog-ng \
        syslog-ng-core \
        logrotate \
        cron \
        wget \
        curl

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-base \
    && bash /opt/docker/bin/control.sh provision.role.finish.build webdevops-cleanup \
    && bash /opt/docker/bin/bootstrap.sh

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["noop"]
