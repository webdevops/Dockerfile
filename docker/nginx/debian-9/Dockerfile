#++++++++++++++++++++++++++++++++++++++
# Debian 9 Nginx Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:debian-9
# <Macro general:version>
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0
# </Macro>

# <Macro environment:web>
ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm
# </Macro>

# <Macro nginx:debian>
# Install nginx
RUN /usr/local/bin/apt-install \
    nginx
# </Macro>

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-nginx \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80 443

CMD ["supervisord"]
