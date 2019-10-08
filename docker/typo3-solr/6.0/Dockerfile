#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/typo3-solr:6.0
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
    && gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys B42F6819007F00F88E364FD4036A9C25BF357DD4 \
    && gpg --batch --verify /tmp/gosu.asc "/baselayout/sbin/gosu" \
    && rm -rf "$GNUPGHOME" /tmp/gosu.asc \
    && chmod +x "/baselayout/sbin/gosu" \
    && "/baselayout/sbin/gosu" nobody true


FROM solr:6.3.0

LABEL maintainer=info@webdevops.io \
      vendor=WebDevOps.io \
      io.webdevops.layout=8 \
      io.webdevops.version=1.5.0

ENV TERM="xterm" \
    LANG="C.UTF-8" \
    LC_ALL="C.UTF-8"

USER root


# Baselayout copy (from staged image)
COPY --from=baselayout /baselayout /


RUN apt-update \
    && apt-install net-tools \
    && generate-dockerimage-info \
    && mkdir /tmp/solr \
    && wget -O/tmp/solr/extension.tar.gz "https://github.com/TYPO3-Solr/ext-solr/archive/6.0.1.tar.gz" \
    && cd /tmp/solr \
    && tar --strip 1 -zxf /tmp/solr/extension.tar.gz \
    && rm -rf /opt/solr/server/solr \
    && mv /tmp/solr/Resources/Private/Solr/ /opt/solr/server/solr \
    && mkdir -p /opt/solr/server/solr/data \
    && chown -R solr:solr /opt/solr/server/solr \
    && chmod 755 /opt/solr/server/solr \
    && rm -rf /tmp/solr \
    && docker-image-cleanup

USER solr

VOLUME ["/opt/solr/server/solr/data"]
