#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/apache:ubuntu-18.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:ubuntu-18.04

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm \
    WEB_PHP_TIMEOUT=600 \
    WEB_PHP_SOCKET=""

COPY conf/ /opt/docker/

RUN set -x \
    # Install apache
    && apt-install \
        apache2 \
    && sed -ri ' \
        s!^(\s*CustomLog)\s+\S+!\1 /proc/self/fd/1!g; \
        s!^(\s*ErrorLog)\s+\S+!\1 /proc/self/fd/2!g; \
        ' /etc/apache2/apache2.conf \
    && rm -f /etc/apache2/sites-enabled/* \
    && ln -sf /opt/docker/etc/httpd/main.conf /etc/apache2/sites-enabled/10-docker.conf \
    && a2enmod actions proxy proxy_fcgi ssl rewrite headers expires \
    && docker-run-bootstrap \
    && docker-image-cleanup

EXPOSE 80 443
