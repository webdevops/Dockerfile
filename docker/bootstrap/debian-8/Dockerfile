#++++++++++++++++++++++++++++++++++++++
# Debian 8 Bootstrap Docker container
#++++++++++++++++++++++++++++++++++++++

FROM debian:8
# <Macro general:version>
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0
# </Macro>

# Ensure UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# Add baselayout (from baselayout/)
ADD baselayout.tar /

# Init bootstrap (and install ansible)
RUN export DEBIAN_FRONTEND=noninteractive \
    && set -x \
    # Fix root terminal
    && echo "export TERM=xterm" >> /root/.bashrc \
    # Enable non-free
    && sed -ri 's/(deb.*\/debian jessie main)/\1 contrib non-free /' -- /etc/apt/sources.list \
    # System update
    && /usr/local/bin/apt-upgrade \
    # Base stuff
    && /usr/local/bin/apt-install \
        lsb-release \
        ca-certificates \
    # Install ansible
        python-dev \
        python-setuptools \
        python-pip \
    && pip install ansible

