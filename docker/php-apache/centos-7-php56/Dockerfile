#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-apache:centos-7-php56
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:centos-7-php56

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm
ENV WEB_PHP_SOCKET=127.0.0.1:9000
ENV WEB_PHP_TIMEOUT=600

COPY conf/ /opt/docker/

# Install apache
RUN /usr/local/bin/yum-install \
        httpd \
        mod_ssl \
	&& sed -ri ' \
		s!^(\s*CustomLog)\s+\S+!\1 /proc/self/fd/1!g; \
		s!^(\s*ErrorLog)\s+\S+!\1 /proc/self/fd/2!g; \
		' /etc/httpd/conf/httpd.conf /etc/httpd/conf.d/ssl.conf \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 80 443
