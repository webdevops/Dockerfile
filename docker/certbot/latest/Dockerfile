#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/certbot:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/bootstrap:alpine

VOLUME /etc/letsencrypt
VOLUME /var/www

RUN /usr/local/bin/apk-install \
        python \
        py-crypto \
        py2-pip \
        py2-dnspython \
        certbot \
    && pip install --upgrade pip \
    && hash -r \
    && pip install pyRFC3339 configobj ConfigArgParse \
    && docker-image-cleanup
