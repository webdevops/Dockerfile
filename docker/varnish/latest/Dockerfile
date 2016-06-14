#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/varnish:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:alpine-3

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=7
LABEL io.webdevops.version=0.50.6

ENV VARNISH_PORT    80

ENV VARNISH_CONFIG  ""
ENV VARNISH_STORAGE "malloc,128m"
ENV VARNISH_OPTS    ""

ENV VARNISH_BACKEND_HOST ""
ENV VARNISH_BACKEND_PORT "80"

COPY conf/ /opt/docker/

RUN /usr/local/bin/apk-install \
        varnish

CMD ["varnishd"]

EXPOSE 80
