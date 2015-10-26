#++++++++++++++++++++++++++++++++++++++
# CentOS 7 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:centos-7
MAINTAINER info@webdevops.io

ENV NGINX_DOCUMENT_ROOT  /docker/code/
ENV NGINX_DOCUMENT_INDEX index.phpa
ENV NGINX_ALIAS_DOMAIN   *.vm

# Install tools
RUN yum -y install \
        nginx \
    && ln -sf /opt/docker/etc/nginx/vhost.conf /etc/nginx/conf.d/10-docker.conf \
    && yum clean all

# Deploy scripts/configurations
COPY . /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role webdevops-php-nginx \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 80
EXPOSE 443

CMD ["supervisord"]
