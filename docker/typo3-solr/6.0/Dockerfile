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

COPY ./solr/ /tmp/solr

USER root

RUN apt-get update \
    && apt-get install -y -f --no-install-recommends lsb-release lsb-base net-tools \
    && apt-get clean -y \
    && rm -rf /opt/solr/server/solr \
    && mv /tmp/solr /opt/solr/server/solr \
    && mkdir -p /opt/solr/server/solr/data \
    && chown -R solr:solr /opt/solr/server/solr

USER solr

VOLUME ["/opt/solr/server/solr/data"]
