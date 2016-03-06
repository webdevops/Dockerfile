#++++++++++++++++++++++++++++++++++++++
# TYPO3 Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-nginx:ubuntu-14.04
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=5
LABEL io.webdevops.version=0.20.0

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
