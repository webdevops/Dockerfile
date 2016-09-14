#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/logstash:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:alpine-3

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=0.56.1

ENV LOGSTASH_VERSION=2.4.0
ENV LOGSTASH_USER  logstash
ENV LOGSTASH_GROUP logstash
ENV LOGSTASH_PATH  /var/lib/logstash
ENV LOGSTASH_UID   2000
ENV LOGSTASH_GID   2000

COPY conf/ /opt/docker/

RUN /usr/local/bin/apk-install \
        openjdk8-jre \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-logstash \
    && /opt/docker/bin/bootstrap.sh

