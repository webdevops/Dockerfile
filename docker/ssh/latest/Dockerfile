#++++++++++++++++++++++++++++++++++++++
# SSH Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base-app:ubuntu-14.04
### BEGIN GENERATED CONTENT :: general:version ###
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0
### END GENERATED CONTENT :: general:version ###

RUN mkdir -p /var/run/sshd \
    &&  bash /opt/docker/bin/control.sh service.enable ssh

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/bootstrap.sh

EXPOSE 22

CMD ["supervisord"]
