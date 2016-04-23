#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/postfix:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:latest

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0

COPY conf/ /opt/docker/

RUN /opt/docker/bin/control.sh service.enable postfix \
    && /opt/docker/bin/provision add --tag bootstrap webdevops-postfix \
    && /opt/docker/bin/bootstrap.sh


EXPOSE 25 465 587
