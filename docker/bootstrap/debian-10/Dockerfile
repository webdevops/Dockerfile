#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/bootstrap:debian-10
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


FROM debian:buster

LABEL maintainer=info@webdevops.io \
      vendor=WebDevOps.io \
      io.webdevops.layout=8 \
      io.webdevops.version=1.5.0

ENV TERM="xterm" \
    LANG="C.UTF-8" \
    LC_ALL="C.UTF-8"


# Baselayout copy (from staged image)
COPY --from=baselayout /baselayout /


RUN set -x \
    # Init bootstrap
    && apt-update \
    && /usr/local/bin/generate-dockerimage-info \
    # Enable non-free
    && sed -ri "s/(deb.*\/debian $(docker-image-info dist-codename) main)/\1 contrib non-free /" -- /etc/apt/sources.list \
    && apt-update \
    # System update
    && /usr/local/bin/apt-upgrade \
    # Base stuff
    && apt-install \
        apt-transport-https \
        ca-certificates \
        locales \
        gnupg
