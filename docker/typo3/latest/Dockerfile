#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/typo3:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-apache:ubuntu-16.04

ENV WEB_DOCUMENT_ROOT /app/web/

RUN composer create-project typo3/cms-base-distribution /app/ \
    && touch /app/web/FIRST_INSTALL \
    && chown -R application /app \
    && find /app/ -type d -exec chmod 0755 {} \; \
    && find /app/ -type f -exec chmod 0644 {} \;
VOLUME /app
