#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/ssh:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:latest

RUN /opt/docker/bin/control.sh service.enable ssh \
    && docker-image-cleanup

EXPOSE 22
