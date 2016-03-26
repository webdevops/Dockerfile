#++++++++++++++++++++++++++++++++++++++
# Alpine 3 Bootstrap Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:alpine-3
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=5
LABEL io.webdevops.version=0.20.0

ENV APPLICATION_USER  application
ENV APPLICATION_GROUP application
ENV APPLICATION_PATH  /app
ENV APPLICATION_UID   1000
ENV APPLICATION_GID   1000

RUN /usr/local/bin/apk-install \
    # Install services
    openssh-server \
    mysql-client \
    sqlite \
    dnsmasq \
    postfix \
    # Install common tools
    sudo \
    zip \
    unzip \
    bzip2 \
    wget \
    curl \
    net-tools \
    moreutils \
    dnsutils \
    openssh-client \
    rsync \
    git \
    nano \
    vim

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-base-app \
    && bash /opt/docker/bin/bootstrap.sh

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["noop"]
