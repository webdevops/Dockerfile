#++++++++++++++++++++++++++++++++++++++
# SSH Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:ubuntu-14.04
MAINTAINER info@webdevops.io

RUN mkdir -p /var/run/sshd \
    &&  bash /opt/docker/bin/control.sh service.enable ssh

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/bootstrap.sh

EXPOSE 22

CMD ["supervisord"]
