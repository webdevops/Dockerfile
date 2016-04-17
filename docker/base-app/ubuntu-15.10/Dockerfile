#++++++++++++++++++++++++++++++++++++++
# Ubuntu 15.10 Base Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:ubuntu-15.10
# <Macro general:version>
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0
# </Macro>

ENV APPLICATION_USER  application
ENV APPLICATION_GROUP application
ENV APPLICATION_PATH  /app
ENV APPLICATION_UID   1000
ENV APPLICATION_GID   1000

# Install services
RUN /usr/local/bin/apt-install \
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
    vim \
    && /usr/local/bin/generate-locales

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-base-app \
    && bash /opt/docker/bin/bootstrap.sh

ENTRYPOINT ["/opt/docker/bin/entrypoint.sh"]
CMD ["noop"]
