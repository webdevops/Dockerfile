#++++++++++++++++++++++++++++++++++++++
# Ubuntu 14.04 HHVM Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:ubuntu-14.04
MAINTAINER info@webdevops.io

# Install tools
RUN /usr/local/bin/apt-install \
        imagemagick \
        graphicsmagick

# Install php
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
    && apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0x5a16e7281be7a449 \
    && add-apt-repository "deb http://dl.hhvm.com/ubuntu $(lsb_release -sc) main" \
    && /usr/local/bin/apt-install \
        hhvm \
    && /usr/bin/update-alternatives --install /usr/bin/php php /usr/bin/hhvm 60 \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-hhvm \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 9000

CMD ["supervisord"]
