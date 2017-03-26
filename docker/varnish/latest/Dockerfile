#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/varnish:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:alpine-3

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.5.0

ENV VARNISH_PORT    80

ENV VARNISH_CONFIG  ""
ENV VARNISH_STORAGE "malloc,128m"
ENV VARNISH_OPTS    ""

ENV VARNISH_BACKEND_HOST ""
ENV VARNISH_BACKEND_PORT "80"

COPY conf/ /opt/docker/

RUN /usr/local/bin/apk-install \
        varnish \
    && /usr/local/bin/docker-image-cleanup

CMD ["varnishd"]

EXPOSE 80
