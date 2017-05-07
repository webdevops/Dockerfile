#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/postfix:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:latest

COPY conf/ /opt/docker/

RUN /opt/docker/bin/control.sh service.enable postfix syslog \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 25 465 587
