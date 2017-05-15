#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/piwik:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-nginx:7.1

ENV WEB_DOCUMENT_ROOT  /app/piwik/
ENV PIWIK_URL          http://example.com/

COPY conf/ /opt/docker/

RUN mkdir -p /app/ \
    && wget -O/tmp/piwik.zip http://builds.piwik.org/piwik.zip \
    && unzip /tmp/piwik.zip -d /app/ \
    && rm -f /tmp/piwik.zip \
    && chown -R application /app \
    && find /app/ -type d -exec chmod 0755 {} \; \
    && find /app/ -type f -exec chmod 0644 {} \;
VOLUME /app
