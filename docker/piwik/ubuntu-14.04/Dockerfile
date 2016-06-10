#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/piwik:ubuntu-14.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-nginx:ubuntu-14.04

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=7
LABEL io.webdevops.version=0.50.5

ENV WEB_DOCUMENT_ROOT  /app/piwik/
ENV PIWIK_URL          http://example.com/

COPY conf/ /opt/docker/

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN /opt/docker/bin/provision add --tag bootstrap --tag entrypoint --tag onbuild webdevops-piwik \
    && /opt/docker/bin/control.sh service.enable cron \
    && /opt/docker/bin/bootstrap.sh

ONBUILD RUN /opt/docker/bin/bootstrap.sh onbuild
ONBUILD VOLUME /app
