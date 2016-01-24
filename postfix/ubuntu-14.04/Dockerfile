#++++++++++++++++++++++++++++++++++++++
# Postfix Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:latest
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=4
LABEL io.webdevops.version=0.17.0

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN /opt/docker/bin/control.sh provision.role.entrypoint webdevops-postfix \
    && /opt/docker/bin/control.sh service.enable postfix \
    && /opt/docker/bin/bootstrap.sh

EXPOSE 25
EXPOSE 465
EXPOSE 587

CMD ["supervisord"]
