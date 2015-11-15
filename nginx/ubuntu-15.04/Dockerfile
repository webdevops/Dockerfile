#++++++++++++++++++++++++++++++++++++++
# Ubuntu 15.04 PHP-Apache Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:ubuntu-15.04
MAINTAINER info@webdevops.io

ENV WEB_DOCUMENT_ROOT  /docker/code/
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

# Install nginx
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
    && apt-get update \
    && apt-get install -y \
        nginx \
	&& ln -sf /opt/docker/etc/nginx/vhost.conf /etc/nginx/conf.d/10-docker.conf \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role webdevops-nginx \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80
EXPOSE 443

CMD ["supervisord"]
