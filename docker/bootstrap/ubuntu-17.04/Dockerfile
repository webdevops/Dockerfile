#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/bootstrap:ubuntu-17.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM ubuntu:17.04

LABEL maintainer=info@webdevops.io \
      vendor=WebDevOps.io \
      io.webdevops.layout=8 \
      io.webdevops.version=1.5.0

ENV TERM="xterm" \
    LANG="C.UTF-8" \
    LC_ALL="C.UTF-8"

ADD baselayout.tar /

# Init bootstrap
RUN set -x \
    && apt-update \
    # System update
    && /usr/local/bin/apt-upgrade \
    # Install base stuff
    && /usr/local/bin/apt-install \
        ca-certificates \
        apt-transport-https \
    && apt-add-repository multiverse \
    && apt-update \
    && /usr/local/bin/generate-dockerimage-info \
    # Install gosu
    && GOSU_VERSION=1.10 \
    && /usr/local/bin/apt-install wget gnupg \
    && dpkgArch="amd64" \
    && wget -O /usr/local/bin/gosu "https://github.com/tianon/gosu/releases/download/$GOSU_VERSION/gosu-$dpkgArch" \
    && wget -O /usr/local/bin/gosu.asc "https://github.com/tianon/gosu/releases/download/$GOSU_VERSION/gosu-$dpkgArch.asc" \
    && export GNUPGHOME="$(mktemp -d)" \
    && gpg --keyserver ha.pool.sks-keyservers.net --recv-keys B42F6819007F00F88E364FD4036A9C25BF357DD4 \
    && gpg --batch --verify /usr/local/bin/gosu.asc /usr/local/bin/gosu \
    && rm -r "$GNUPGHOME" /usr/local/bin/gosu.asc \
    && chmod +x /usr/local/bin/gosu \
    && gosu nobody true \
    ## Install go-replace
    && GOREPLACE_VERSION=1.1.2 \
    && wget -O /usr/local/bin/go-replace https://github.com/webdevops/goreplace/releases/download/$GOREPLACE_VERSION/gr-64-linux \
    && chmod +x /usr/local/bin/go-replace \
    && apt-get purge -y -f --force-yes wget \
    && docker-image-cleanup
