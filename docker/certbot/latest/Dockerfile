#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/certbot:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:alpine-3

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=0.56.1

VOLUME /etc/letsencrypt
VOLUME /var/www

RUN /usr/local/bin/apk-install \
        certbot
