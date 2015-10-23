#++++++++++++++++++++++++++++++++++++++
# SSH Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:latest
MAINTAINER info@webdevops.io

RUN mkdir -p /var/run/sshd \
    && sed -i '/autostart = /c\autostart = true' /opt/docker/etc/supervisor.d/ssh.conf

EXPOSE 22

CMD ["supervisord"]