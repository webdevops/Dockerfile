#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/postfix:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:latest

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.3.1

COPY conf/ /opt/docker/

RUN /opt/docker/bin/control.sh service.enable postfix \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-postfix \
    && /opt/docker/bin/bootstrap.sh


EXPOSE 25 465 587
