#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/typo3-solr:3.1
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++


FROM guywithnose/solr:4.10.4

LABEL maintainer=info@webdevops.io \
      vendor=WebDevOps.io \
      io.webdevops.layout=8 \
      io.webdevops.version=1.5.0

ENV TERM="xterm" \
    LANG="C.UTF-8" \
    LC_ALL="C.UTF-8"

USER root

COPY ./solr/ /tmp/solr


# Baselayout copy (from staged image)
COPY --from=webdevops/toolbox /baselayout/sbin/* /sbin/
COPY --from=webdevops/toolbox /baselayout/usr/local/bin/* /usr/local/bin/


RUN pacman --sync --noconfirm --noprogressbar --quiet net-tools \
    && rm -rf /opt/solr/server \
    && mv /opt/solr/example/ /opt/solr/server/ \
    && rm -rf /opt/solr/server/solr \
    && mv /tmp/solr /opt/solr/server/solr \
    && mkdir -p /opt/solr/server/solr/typo3lib \
    && curl -sf -o /opt/solr/server/solr/typo3lib/solr-typo3-plugin.jar -L https://github.com/TYPO3-Solr/solr-typo3-plugin/releases/download/release-1_3_0/solr-typo3-plugin-1.3.0.jar \
    && ln -s /opt/solr/contrib /opt/solr/server/solr/contrib \
    && mkdir -p /opt/solr/server/solr/data \
    && ln -s /opt/solr/server/solr/data /opt/solr/server/solr/typo3cores/data \
    && chown -R solr:solr /opt/solr/server/solr/

USER solr

WORKDIR /opt/solr/server

VOLUME ["/opt/solr/server/solr/data"]
