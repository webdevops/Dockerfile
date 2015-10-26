#++++++++++++++++++++++++++++++++++++++
# Ubuntu 14.04 PHP-Apache Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:ubuntu-14.04
MAINTAINER info@webdevops.io

ENV NGINX_DOCUMENT_ROOT  /docker/code/
ENV NGINX_DOCUMENT_INDEX index.php
ENV NGINX_ALIAS_DOMAIN   *.vm

# Install nginx
RUN set -x \
    && apt-get update \
    && apt-get install -y \
        nginx \
	&& ln -sf /opt/docker/etc/nginx/vhost.conf /etc/nginx/conf.d/10-docker.conf \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role webdevops-php-nginx \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80
EXPOSE 443

CMD ["supervisord"]
