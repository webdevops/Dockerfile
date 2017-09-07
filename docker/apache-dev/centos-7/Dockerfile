#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/apache-dev:centos-7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/apache:centos-7

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm
    WEB_PHP_TIMEOUT=600

COPY conf/ /opt/docker/



EXPOSE 80 443
