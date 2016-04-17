#++++++++++++++++++++++++++++++++++++++
# Debian 7 Apache Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:debian-7
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

# <Macro apache:debian-8>
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
	&& a2enmod actions fastcgi ssl rewrite headers
# </Macro>

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-apache \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80 443

CMD ["supervisord"]
