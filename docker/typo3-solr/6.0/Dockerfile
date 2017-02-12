#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/typo3-solr:6.0
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM solr:6.3.0

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.4.0

ENV TERM   "xterm"
ENV LANG   "C.UTF-8"
ENV LC_ALL "C.UTF-8"

RUN rm -fR /opt/solr/server/solr/*

COPY ./solr/ /opt/solr/server/solr

USER root

RUN mkdir -p /opt/solr/server/solr/data \
    && chown -R solr:solr /opt/solr/server/solr/

USER solr

VOLUME ["/opt/solr/server/solr/data"]
