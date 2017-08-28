#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-apache:alpine-php7
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:alpine-php7

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm
ENV WEB_PHP_SOCKET=127.0.0.1:9000
ENV WEB_PHP_TIMEOUT=600

COPY conf/ /opt/docker/

# Install apache
RUN /usr/local/bin/apk-install \
        apache2 \
        apache2-utils \
        apache2-proxy \
        apache2-ssl \
	&& sed -ri ' \
		s!^(\s*CustomLog)\s+\S+!\1 /proc/self/fd/1!g; \
		s!^(\s*ErrorLog)\s+\S+!\1 /proc/self/fd/2!g; \
		' /etc/apache2/httpd.conf \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 80 443
