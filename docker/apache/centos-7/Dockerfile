#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/apache:centos-7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:centos-7

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm \
    WEB_PHP_TIMEOUT=600 \
    WEB_PHP_SOCKET=""

COPY conf/ /opt/docker/

RUN set -x \
    # Install apache
    && yum-install \
        httpd \
        mod_ssl \
	&& sed -ri ' \
		s!^(\s*CustomLog)\s+\S+!\1 /proc/self/fd/1!g; \
		s!^(\s*ErrorLog)\s+\S+!\1 /proc/self/fd/2!g; \
		' /etc/httpd/conf/httpd.conf /etc/httpd/conf.d/ssl.conf \
    && docker-run-bootstrap \
    && docker-image-cleanup

EXPOSE 80 443
