#++++++++++++++++++++++++++++++++++++++
# TYPO3 Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-apache:ubuntu-14.04
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.21.0

ENV WEB_DOCUMENT_ROOT /app/web/

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN /opt/docker/bin/control.sh provision.role.entrypoint webdevops-typo3 \
    && /opt/docker/bin/bootstrap.sh

ONBUILD RUN composer create-project typo3/cms-base-distribution /app/
ONBUILD VOLUME /app

EXPOSE 80 443
