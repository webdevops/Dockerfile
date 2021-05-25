#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/bootstrap:ubuntu-15.10
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++


FROM ubuntu:15.10

LABEL maintainer=info@webdevops.io \
      vendor=WebDevOps.io \
      io.webdevops.layout=8 \
      io.webdevops.version=1.5.0

ENV TERM="xterm" \
    LANG="C.UTF-8" \
    LC_ALL="C.UTF-8"


# Baselayout copy (from staged image)
COPY --from=webdevops/toolbox /baselayout/sbin/* /sbin/
COPY --from=webdevops/toolbox /baselayout/usr/local/bin/* /usr/local/bin/


RUN set -x \
    # Init bootstrap
    # Add multiverse
    && sed -i 's/archive.ubuntu.com\|security.ubuntu.com/old-releases.ubuntu.com/g' /etc/apt/sources.list \
    && apt-update \
    # System update
    && /usr/local/bin/apt-upgrade \
    # Install base stuff
    && apt-install \
        apt-transport-https \
        python-software-properties \
        ca-certificates \
        apt-transport-https \
    && apt-add-repository multiverse \
    && sed -i 's/archive.ubuntu.com\|security.ubuntu.com/old-releases.ubuntu.com/g' /etc/apt/sources.list \
    && apt-update \
    && /usr/local/bin/generate-dockerimage-info \
    && docker-run-bootstrap \
    && docker-image-cleanup
