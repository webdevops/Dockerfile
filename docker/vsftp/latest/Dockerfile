#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/vsftp:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:latest

ENV FTP_USER=application \
    FTP_PASSWORD=application \
    FTP_UID=1000 \
    FTP_GID=1000 \
    FTP_PATH=/data/ftp/

COPY conf/ /opt/docker/

# Install vsftp
RUN /usr/local/bin/apt-install \
        vsftpd \
    && ln -sf /opt/docker/etc/vsftpd/vsftpd.conf /etc/vsftpd.conf \
    && mkdir -p \
            /var/run/vsftpd/empty \
            /var/log/supervisor \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 20 21 12020 12021 12022 12023 12024 12025
