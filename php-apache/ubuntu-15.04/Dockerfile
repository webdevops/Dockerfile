#++++++++++++++++++++++++++++++++++++++
# Ubuntu 15.04 PHP-Apache Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:ubuntu-15.04
MAINTAINER info@webdevops.io

ENV WEB_DOCUMENT_ROOT  /docker/code/
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

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
	&& ln -sf /opt/docker/etc/httpd/main.conf /etc/apache2/sites-enabled/10-docker.conf \
	&& a2enmod actions fastcgi ssl rewrite

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role webdevops-php-apache \
    && bash /opt/docker/bin/bootstrap.sh

RUN chmod +x /opt/docker/bin/service.d/httpd.sh

EXPOSE 80
EXPOSE 443

CMD ["supervisord"]
