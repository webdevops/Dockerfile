#++++++++++++++++++++++++++++++++++++++
# Postfix Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:latest
### BEGIN GENERATED CONTENT :: general:version ###
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0
### END GENERATED CONTENT :: general:version ###

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN /opt/docker/bin/control.sh provision.role.entrypoint webdevops-postfix \
    && /opt/docker/bin/control.sh service.enable postfix \
    && /opt/docker/bin/bootstrap.sh

EXPOSE 25 465 587

CMD ["supervisord"]
