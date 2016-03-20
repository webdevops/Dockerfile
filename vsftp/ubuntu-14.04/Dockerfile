#++++++++++++++++++++++++++++++++++++++
# VSFTP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:latest
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=5
LABEL io.webdevops.version=0.21.0

ENV FTP_USER     application
ENV FTP_PASSWORD application
ENV FTP_UID      1000
ENV FTP_GID      1000
ENV FTP_PASSWORD dev
ENV FTP_PATH     /data/ftp/

# Install vsftp
RUN /usr/local/bin/apt-install \
        vsftpd \
    && ln -sf /opt/docker/etc/vsftpd/vsftpd.conf /etc/vsftpd.conf \
    && mkdir -p \
            /var/run/vsftpd/empty \
            /var/log/supervisor

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN /opt/docker/bin/control.sh provision.role webdevops-vsftp \
    && /opt/docker/bin/bootstrap.sh

EXPOSE 20 21 12020 12021 12022 12023 12024 12025

CMD ["supervisord"]
