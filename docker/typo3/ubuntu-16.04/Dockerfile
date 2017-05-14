#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/typo3:ubuntu-16.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-apache:ubuntu-16.04

ENV WEB_DOCUMENT_ROOT /app/web/

ONBUILD RUN composer create-project typo3/cms-base-distribution /app/
ONBUILD VOLUME /app
