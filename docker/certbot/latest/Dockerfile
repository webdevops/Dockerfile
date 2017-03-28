#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/certbot:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:alpine-3

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.5.0

VOLUME /etc/letsencrypt
VOLUME /var/www

RUN /usr/local/bin/apk-install \
        python \
        py-crypto \
        py2-pip \
        certbot \
    && pip install --upgrade pip \
    && hash -r \
    && pip install pyRFC3339 configobj ConfigArgParse \
    && /usr/local/bin/docker-image-cleanup
