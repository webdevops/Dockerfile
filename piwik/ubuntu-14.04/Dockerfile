#++++++++++++++++++++++++++++++++++++++
# TYPO3 Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-nginx:ubuntu-14.04
MAINTAINER info@webdevops.io

ENV WEB_DOCUMENT_ROOT  /application/code/piwik/
ENV PIWIK_URL          http://example.com/

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN /opt/docker/bin/control.sh provision.role webdevops-piwik \
    && /opt/docker/bin/control.sh service.enable cron \
    && /opt/docker/bin/bootstrap.sh

ONBUILD RUN /opt/docker/bin/bootstrap.sh onbuild

EXPOSE 80
EXPOSE 443
