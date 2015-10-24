#++++++++++++++++++++++++++++++++++++++
# SSH Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:latest
MAINTAINER info@webdevops.io

RUN mkdir -p /var/run/sshd \
    && sed -i '/autostart = /c\autostart = true' /opt/docker/etc/supervisor.d/ssh.conf

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/bootstrap.sh

EXPOSE 22

CMD ["supervisord"]