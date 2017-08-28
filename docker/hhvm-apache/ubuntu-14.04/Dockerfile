#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/hhvm-apache:ubuntu-14.04
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/hhvm:ubuntu-14.04

ENV WEB_DOCUMENT_ROOT=/app \
    WEB_DOCUMENT_INDEX=index.php \
    WEB_ALIAS_DOMAIN=*.vm
ENV WEB_PHP_SOCKET=127.0.0.1:9000
ENV WEB_PHP_TIMEOUT=600

COPY conf/ /opt/docker/

# Install apache
RUN /usr/local/bin/apt-install \
        apache2 \
        apache2-mpm-worker \
        libapache2-mod-fastcgi \
	&& sed -ri ' \
		s!^(\s*CustomLog)\s+\S+!\1 /proc/self/fd/1!g; \
		s!^(\s*ErrorLog)\s+\S+!\1 /proc/self/fd/2!g; \
		' /etc/apache2/apache2.conf \
	&& rm -f /etc/apache2/sites-enabled/* \
	&& a2enmod actions fastcgi ssl rewrite headers expires \
	&& mkdir -p /var/lock/apache2 \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

EXPOSE 80 443
