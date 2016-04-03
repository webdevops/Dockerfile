#++++++++++++++++++++++++++++++++++++++
# CentOS 7 Base Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:centos-7
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.21.0

ENV DOCKER_CONF_HOME  /opt/docker/

# Install services
RUN /usr/local/bin/yum-install \
    cronie \
    supervisor \
    syslog-ng \
    logrotate \
    wget \
    curl \
    net-tools

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-base \
    && bash /opt/docker/bin/control.sh provision.role.finish.build webdevops-cleanup \
    && bash /opt/docker/bin/bootstrap.sh

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["noop"]
