#++++++++++++++++++++++++++++++++++++++
# CentOS 7 PHP-Apache Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:centos-7
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=4
LABEL io.webdevops.version=0.19.0

ENV WEB_DOCUMENT_ROOT  /application/code/
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm

# Install tools
RUN /usr/local/bin/yum-install \
        httpd \
        mod_ssl \
	&& sed -ri ' \
		s!^(\s*CustomLog)\s+\S+!\1 /proc/self/fd/1!g; \
		s!^(\s*ErrorLog)\s+\S+!\1 /proc/self/fd/2!g; \
		' /etc/httpd/conf/httpd.conf /etc/httpd/conf.d/ssl.conf

# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role webdevops-apache \
    && bash /opt/docker/bin/control.sh provision.role webdevops-php-apache \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80
EXPOSE 443

CMD ["supervisord"]
