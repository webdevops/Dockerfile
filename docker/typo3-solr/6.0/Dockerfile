#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/typo3-solr:6.0
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM solr:6.3.0

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.5.0

ENV TERM   "xterm"
ENV LANG   "C.UTF-8"
ENV LC_ALL "C.UTF-8"

USER root

ADD baselayout.tar /

COPY ./solr/ /tmp/solr

RUN /usr/local/bin/apt-update \
    && /usr/local/bin/apt-install net-tools \
    && /usr/local/bin/generate-dockerimage-info \
    && rm -rf /opt/solr/server/solr \
    && mv /tmp/solr /opt/solr/server/solr \
    && mkdir -p /opt/solr/server/solr/data \
    && chown -R solr:solr /opt/solr/server/solr \
    && /usr/local/bin/docker-image-cleanup

USER solr

VOLUME ["/opt/solr/server/solr/data"]
