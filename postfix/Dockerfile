#++++++++++++++++++++++++++++++++++++++
# Postfix Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:latest
MAINTAINER info@webdevops.io

# Deploy scripts/configurations
COPY . /opt/docker/
RUN /opt/docker/bin/control.sh provision.role.entrypoint webdevops-postfix \
    && /opt/docker/bin/control.sh service.enable postfix \
    && /opt/docker/bin/bootstrap.sh

EXPOSE 25
EXPOSE 465
EXPOSE 587

CMD ["supervisord"]
