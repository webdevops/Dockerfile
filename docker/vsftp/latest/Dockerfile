#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/vsftp:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:latest

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.5.0

ENV FTP_USER     application
ENV FTP_PASSWORD application
ENV FTP_UID      1000
ENV FTP_GID      1000
ENV FTP_PATH     /data/ftp/

COPY conf/ /opt/docker/

# Install vsftp
RUN /usr/local/bin/apt-install \
        vsftpd \
    && ln -sf /opt/docker/etc/vsftpd/vsftpd.conf /etc/vsftpd.conf \
    && mkdir -p \
            /var/run/vsftpd/empty \
            /var/log/supervisor \
    && /opt/docker/bin/provision add --tag entrypoint  webdevops-vsftp \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-vsftp \
    && /opt/docker/bin/bootstrap.sh \
    && /usr/local/bin/docker-image-cleanup

EXPOSE 20 21 12020 12021 12022 12023 12024 12025
