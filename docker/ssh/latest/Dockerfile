#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/ssh:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:latest

RUN /opt/docker/bin/control.sh service.enable ssh \
    && /usr/local/bin/docker-image-cleanup

EXPOSE 22
