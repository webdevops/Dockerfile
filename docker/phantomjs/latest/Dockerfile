#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/phantomjs:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/node:latest

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=7
LABEL io.webdevops.version=0.50.5

COPY conf/ /opt/docker/

ENV PHANTOMJS_PORT				 8065
ENV PHANTOMJS_IGNORE_SSL_ERRORS  true
ENV PHANTOMJS_LOAD_IMAGES        true

RUN /usr/local/bin/apt-install \
        fontconfig \
        libfreetype6 \
        libfontconfig \
    && npm install -g phantomjs

EXPOSE 8065

CMD ["supervisord"]