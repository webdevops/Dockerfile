#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/postfix:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:latest

COPY conf/ /opt/docker/

RUN set -x \
    && docker-service enable syslog \
    && docker-service enable postfix \
    && docker-run-bootstrap \
    && docker-image-cleanup

EXPOSE 25 465 587
