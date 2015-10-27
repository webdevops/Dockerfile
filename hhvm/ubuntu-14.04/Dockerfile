#++++++++++++++++++++++++++++++++++++++
# Ubuntu 14.04 HHVM Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:ubuntu-14.04
MAINTAINER info@webdevops.io

# Ensure UTF-8
RUN locale-gen en_US.UTF-8
ENV LANG       en_US.UTF-8
ENV LC_ALL     en_US.UTF-8

# Install tools
RUN set -x \
    && apt-get update \
    && apt-get install -y \
        imagemagick \
        graphicsmagick \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Install php
RUN set -x \
    && apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0x5a16e7281be7a449 \
    && add-apt-repository "deb http://dl.hhvm.com/ubuntu $(lsb_release -sc) main" \
    && apt-get update \
    && apt-get install -y \
        hhvm \
    && /usr/bin/update-alternatives --install /usr/bin/php php /usr/bin/hhvm 60 \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/bootstrap.sh

# Configure volume/workdir
RUN mkdir -p /docker/code/
VOLUME /docker/
WORKDIR /docker/code/

EXPOSE 9000

CMD ["supervisord"]
