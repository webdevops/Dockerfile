#++++++++++++++++++++++++++++++++++++++
# TYPO3 Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-nginx:ubuntu-14.04
### BEGIN GENERATED CONTENT :: general:version ###
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0
### END GENERATED CONTENT :: general:version ###

ENV WEB_DOCUMENT_ROOT  /app/piwik/
ENV PIWIK_URL          http://example.com/

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN /opt/docker/bin/control.sh provision.role webdevops-piwik \
    && /opt/docker/bin/control.sh service.enable cron \
    && /opt/docker/bin/bootstrap.sh

ONBUILD RUN /opt/docker/bin/bootstrap.sh onbuild
ONBUILD VOLUME /app

EXPOSE 80 443
