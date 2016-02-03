#++++++++++++++++++++++++++++++++++++++
# Ubuntu 16.04 Nginx Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:ubuntu-16.04
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=4
LABEL io.webdevops.version=0.18.0

ENV WEB_DOCUMENT_ROOT  /application/code/
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

# Install nginx
RUN /usr/local/bin/apt-install \
        nginx \
	&& ln -sf /opt/docker/etc/nginx/vhost.conf /etc/nginx/conf.d/10-docker.conf

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role webdevops-nginx \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80
EXPOSE 443

CMD ["supervisord"]
