#++++++++++++++++++++++++++++++++++++++
# Ubuntu 14.04 HHVM-Apache Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/hhvm:ubuntu-14.04
MAINTAINER info@webdevops.io

ENV WEB_DOCUMENT_ROOT  /docker/code/
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

# Install nginx
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
    && apt-get update \
    && apt-get install -y \
        nginx \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role webdevops-hhvm-nginx \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80
EXPOSE 443

CMD ["supervisord"]
