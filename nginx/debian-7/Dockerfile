#++++++++++++++++++++++++++++++++++++++
# Debian 7 Nginx Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:debian-7
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=5
LABEL io.webdevops.version=0.21.0

ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

# Install nginx
RUN /usr/local/bin/apt-install \
        nginx

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-nginx \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80 443

CMD ["supervisord"]
