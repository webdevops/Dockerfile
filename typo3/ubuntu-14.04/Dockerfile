#++++++++++++++++++++++++++++++++++++++
# TYPO3 Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-apache:ubuntu-14.04
MAINTAINER info@webdevops.io

ENV WEB_DOCUMENT_ROOT /application/code/web/

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN /opt/docker/bin/control.sh provision.role.entrypoint webdevops-typo3 \
    && /opt/docker/bin/bootstrap.sh

ONBUILD RUN composer create-project typo3/cms-base-distribution /application/code/

EXPOSE 80
EXPOSE 443
