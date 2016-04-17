#++++++++++++++++++++++++++++++++++++++
# Ubuntu 15.10 PHP-Apache Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:ubuntu-15.10
# <Macro general:version>
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0
# </Macro>

# <Macro environment:web>
ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm
# </Macro>

# <Macro apache:ubuntu-16>
# Install apache
RUN /usr/local/bin/apt-install \
        apache2 \
        libapache2-mod-fastcgi \
	&& sed -ri ' \
		s!^(\s*CustomLog)\s+\S+!\1 /proc/self/fd/1!g; \
		s!^(\s*ErrorLog)\s+\S+!\1 /proc/self/fd/2!g; \
		' /etc/apache2/apache2.conf \
	&& rm -f /etc/apache2/sites-enabled/* \
	&& ln -sf /opt/docker/etc/httpd/main.conf /etc/apache2/sites-enabled/10-docker.conf \
	&& a2enmod actions fastcgi ssl rewrite headers version
# </Macro>

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-apache \
    && bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-php-apache \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80 443

CMD ["supervisord"]
