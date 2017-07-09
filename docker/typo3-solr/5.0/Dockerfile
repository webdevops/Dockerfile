#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/typo3-solr:5.0
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++


# Staged baselayout builder
FROM webdevops/toolbox AS baselayout
RUN mkdir -p \
        /baselayout/sbin \
        /baselayout/usr/local/bin \
    # Baselayout scripts
    && wget -O /tmp/baselayout-install.sh https://raw.githubusercontent.com/webdevops/Docker-Image-Baselayout/master/install.sh \
    && sh /tmp/baselayout-install.sh /baselayout \
    ## Install go-replace
    && wget -O "/baselayout/usr/local/bin/go-replace" "https://github.com/webdevops/goreplace/releases/download/1.1.2/gr-64-linux" \
    && chmod +x "/baselayout/usr/local/bin/go-replace" \
    && "/baselayout/usr/local/bin/go-replace" --version \
    # Install gosu
    && wget -O "/baselayout/sbin/gosu" "https://github.com/tianon/gosu/releases/download/1.10/gosu-amd64" \
    && wget -O "/tmp/gosu.asc" "https://github.com/tianon/gosu/releases/download/1.10/gosu-amd64.asc" \
    && export GNUPGHOME="$(mktemp -d)" \
    && gpg --keyserver ha.pool.sks-keyservers.net --recv-keys B42F6819007F00F88E364FD4036A9C25BF357DD4 \
    && gpg --batch --verify /tmp/gosu.asc "/baselayout/sbin/gosu" \
    && rm -rf "$GNUPGHOME" /tmp/gosu.asc \
    && chmod +x "/baselayout/sbin/gosu" \
    && "/baselayout/sbin/gosu" nobody true


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
COPY --from=baselayout /baselayout /


WORKDIR /

RUN pacman --sync --noconfirm --noprogressbar --quiet net-tools \
    && /usr/local/bin/generate-dockerimage-info \
    && rm -rf /opt/solr/server \
    && mv /opt/solr/example/ /opt/solr/server/ \
    && rm -rf /opt/solr/server/solr \
    && mv /tmp/solr /opt/solr/server/solr \
    && mkdir -p /opt/solr/server/solr/typo3lib \
    && curl -sf -o /opt/solr/server/solr/typo3lib/solr-typo3-plugin.jar -L https://github.com/TYPO3-Solr/solr-typo3-plugin/releases/download/release-1_3_0/solr-typo3-plugin-1.3.0.jar \
    && ln -s /opt/solr/contrib /opt/solr/server/solr/contrib \
    && mkdir -p /opt/solr/server/solr/data \
    && ln -s /opt/solr/server/solr/data /opt/solr/server/solr/typo3cores/data \
    && chown -R solr:solr /opt/solr/server/solr/ \
    && docker-image-cleanup

USER solr

WORKDIR /opt/solr/server

VOLUME ["/opt/solr/server/solr/data"]

