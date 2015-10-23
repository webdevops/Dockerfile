#++++++++++++++++++++++++++++++++++++++
# Ubuntu 14.04 VSFTP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:latest
MAINTAINER info@webdevops.io

ENV FTP_USER     dev
ENV FTP_UID      1000
ENV FTP_GID      1000
ENV FTP_PASSWORD dev
ENV FTP_PATH     /data/ftp/

# Install vsftp
RUN set -x \
    && apt-get update \
    && apt-get install -y \
        vsftpd \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

RUN mkdir -p \
    /var/run/vsftpd/empty \
    /var/log/supervisor \
    /data/ftp/

COPY bin /opt/docker/bin/
COPY etc /opt/docker/etc/

COPY conf/vsftpd.conf      /etc/vsftpd.conf

VOLUME "/data/ftp/"

EXPOSE 20
EXPOSE 21
EXPOSE 12020
EXPOSE 12021
EXPOSE 12022
EXPOSE 12023
EXPOSE 12024
EXPOSE 12025

CMD ["supervisord"]