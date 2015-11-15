#++++++++++++++++++++++++++++++++++++++
# Ubuntu 14.04 HHVM-Apache Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/hhvm:ubuntu-14.04
MAINTAINER info@webdevops.io

ENV WEB_DOCUMENT_ROOT  /docker/code/
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

# Install apache
RUN export DEBIAN_FRONTEND=noninteractive && set -x \
    && apt-get update \
    && apt-get install -y \
        apache2 \
        apache2-mpm-worker \
        libapache2-mod-fastcgi \
	&& sed -ri ' \
		s!^(\s*CustomLog)\s+\S+!\1 /proc/self/fd/1!g; \
		s!^(\s*ErrorLog)\s+\S+!\1 /proc/self/fd/2!g; \
		' /etc/apache2/apache2.conf \
	&& rm -f /etc/apache2/sites-enabled/* \
	&& a2enmod actions fastcgi ssl \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean -y

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role webdevops-hhvm-apache \
    && bash /opt/docker/bin/bootstrap.sh

RUN chmod +x /opt/docker/bin/service.d/httpd.sh

EXPOSE 80
EXPOSE 443

CMD ["supervisord"]
