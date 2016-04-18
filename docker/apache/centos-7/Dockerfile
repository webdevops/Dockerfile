#++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/apache:centos-7
# -- automatically generated content --
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/base:centos-7

<Macro 'version'>

<Macro 'environmentWeb'>

# Install apache
RUN /usr/local/bin/yum-install \
        httpd \
        mod_ssl \
	&& sed -ri ' \
		s!^(\s*CustomLog)\s+\S+!\1 /proc/self/fd/1!g; \
		s!^(\s*ErrorLog)\s+\S+!\1 /proc/self/fd/2!g; \
		' /etc/httpd/conf/httpd.conf /etc/httpd/conf.d/ssl.conf

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-apache \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80 443

CMD ["supervisord"]
