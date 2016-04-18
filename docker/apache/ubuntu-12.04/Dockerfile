#++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/apache:ubuntu-12.04
# -- automatically generated content --
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:ubuntu-12.04

<Macro 'version'>

<Macro 'environmentWeb'>

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
	&& a2enmod actions fastcgi ssl rewrite headers version \
	&& mkdir -p /var/lock/apache2

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-apache \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80 443

CMD ["supervisord"]
