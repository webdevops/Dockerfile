#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/typo3-solr:6.0
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM solr:6.3.0

LABEL maintainer=info@webdevops.io \
      vendor=WebDevOps.io \
      io.webdevops.layout=8 \
      io.webdevops.version=1.5.0

ENV TERM="xterm" \
    LANG="C.UTF-8" \
    LC_ALL="C.UTF-8"

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
    && docker-image-cleanup

USER solr

VOLUME ["/opt/solr/server/solr/data"]
