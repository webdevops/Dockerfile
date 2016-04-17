#++++++++++++++++++++++++++++++++++++++
# Ubuntu 14.04 HHVM-Apache Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/hhvm:ubuntu-14.04
### BEGIN GENERATED CONTENT :: general:version ###
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0
### END GENERATED CONTENT :: general:version ###

ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

# Install nginx
RUN /usr/local/bin/apt-install \
        nginx

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-nginx \
    && bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-hhvm-nginx \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80 443

CMD ["supervisord"]
