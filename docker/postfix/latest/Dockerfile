#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/postfix:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:latest

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=7
LABEL io.webdevops.version=0.50.0

COPY conf/ /opt/docker/

RUN /opt/docker/bin/control.sh service.enable postfix \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-postfix \
    && /opt/docker/bin/bootstrap.sh


EXPOSE 25 465 587
