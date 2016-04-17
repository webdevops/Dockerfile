#++++++++++++++++++++++++++++++++++++++
# Ubuntu 15.04 PHP-Nginx Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:ubuntu-15.04
### BEGIN GENERATED CONTENT :: general:version ###
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0
### END GENERATED CONTENT :: general:version ###

ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

### BEGIN GENERATED CONTENT :: nginx:ubuntu ###
# Install nginx
RUN /usr/local/bin/apt-install \
    nginx
### END GENERATED CONTENT :: nginx:ubuntu ###

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-nginx \
    && bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-php-nginx \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80 443

CMD ["supervisord"]
