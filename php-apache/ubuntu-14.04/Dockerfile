#++++++++++++++++++++++++++++++++++++++
# Ubuntu 14.04 PHP-Apache Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:ubuntu-14.04
MAINTAINER info@webdevops.io

ENV HTTPD_DOCUMENT_ROOT  /docker/code/
ENV HTTPD_DOCUMENT_INDEX index.phpa
ENV HTTPD_ALIAS_DOMAIN   *.vm

# Install tools
RUN apt-add-repository multiverse

# Install php
RUN set -x \
    && apt-get update \
    && apt-get install -y \
        apache2 \
        apache2-mpm-worker \
        libapache2-mod-macro \
        libapache2-mod-fastcgi \
	&& sed -ri ' \
		s!^(\s*CustomLog)\s+\S+!\1 /proc/self/fd/1!g; \
		s!^(\s*ErrorLog)\s+\S+!\1 /proc/self/fd/2!g; \
		' /etc/apache2/apache2.conf \
	&& rm -f /etc/apache2/sites-enabled/* \
	&& ln -sf /opt/docker/etc/httpd/vhost.conf /etc/apache2/sites-enabled/10-docker.conf \
	&& a2enmod actions fastcgi ssl \
    && sed -i '/listen = /c\listen = /var/run/php5-fpm.sock' /etc/php5/fpm/pool.d/www.conf \
    && sed -i '/listen.allowed_clients = /c\;listen.allowed_clients = 0.0.0.0' /etc/php5/fpm/pool.d/www.conf \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/bootstrap.sh

RUN chmod +x /opt/docker/bin/service.d/httpd.sh

EXPOSE 80
EXPOSE 443

CMD ["supervisord"]