#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/typo3:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-apache:7.2

ENV WEB_DOCUMENT_ROOT /app/public/

RUN set -x \
    && composer create-project typo3/cms-base-distribution /app/ \
    && touch /app/public/FIRST_INSTALL \
    && chown -R application /app \
    && find /app/ -type d -exec chmod 0755 {} \; \
    && find /app/ -type f -exec chmod 0644 {} \; \
    && docker-run-bootstrap \
    && docker-image-cleanup

VOLUME /app
