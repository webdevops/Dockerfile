#++++++++++++++++++++++++++++++++++++++
# VSFTP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:latest
MAINTAINER info@webdevops.io

ENV FTP_USER     dev
ENV FTP_PASSWORD dev
ENV FTP_UID      1000
ENV FTP_GID      1000
ENV FTP_PASSWORD dev
ENV FTP_PATH     /data/ftp/

# Install vsftp
RUN set -x \
    && apt-get update \
    && apt-get install -y \
        vsftpd \
    && ln -sf /opt/docker/etc/vsftpd.conf /etc/vsftpd.conf \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

RUN mkdir -p \
    /var/run/vsftpd/empty \
    /var/log/supervisor \
    /data/ftp/

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/bootstrap.sh

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
