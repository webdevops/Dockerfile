#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/apache:alpine-php5
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:alpine

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm

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
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-apache \
    && /opt/docker/bin/bootstrap.sh \
    && /usr/local/bin/docker-image-cleanup

EXPOSE 80 443
