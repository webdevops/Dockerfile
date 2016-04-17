#++++++++++++++++++++++++++++++++++++++
# Alpine PHP-Nginx Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:alpine-3-php7
# <Macro general:version>
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0
# </Macro>

ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

# <Macro nginx:alpine>
# Install nginx
RUN /usr/local/bin/apk-install \
    nginx
# </Macro>

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-nginx \
    && bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-php-nginx \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80 443

CMD ["supervisord"]
