#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/bootstrap:centos-7
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


FROM centos:7

LABEL maintainer=info@webdevops.io \
      vendor=WebDevOps.io \
      io.webdevops.layout=8 \
      io.webdevops.version=1.5.0

ENV TERM="xterm" \
    LANG="en_US.utf8" \
    LC_ALL="en_US.utf8"


# Baselayout copy (from staged image)
COPY --from=baselayout /baselayout /


# Init bootstrap
RUN set -x \
    # System update
    && /usr/local/bin/yum-upgrade \
    && /usr/local/bin/yum-install \
        epel-release \
    && /usr/local/bin/generate-dockerimage-info \
    && docker-image-cleanup
