#++++++++++++++++++++++++++++++++++++++
# TYPO3 Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-nginx:ubuntu-14.04
MAINTAINER info@webdevops.io

ENV WEB_DOCUMENT_ROOT  /application/code/piwik/

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN /opt/docker/bin/control.sh provision.role.onbuild webdevops-piwik \
    && /opt/docker/bin/bootstrap.sh

ONBUILD RUN /opt/docker/bin/bootstrap.sh onbuild

EXPOSE 80
EXPOSE 443
