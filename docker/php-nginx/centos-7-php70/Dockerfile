#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/php-nginx:centos-7-php56
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:centos-7-php56

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=0.57.1

ENV WEB_DOCUMENT_ROOT  /app
ENV WEB_DOCUMENT_INDEX index.php
ENV WEB_ALIAS_DOMAIN   *.vm
ENV WEB_PHP_SOCKET  127.0.0.1:9000

COPY conf/ /opt/docker/

# Remove php5
RUN yum -y remove php*

# Install php 7
RUN wget https://centos7.iuscommunity.org/ius-release.rpm
RUN /usr/local/bin/yum-install ius-release.rpm
RUN /usr/local/bin/yum-install php70u-*

# Install tools
RUN /usr/local/bin/yum-install \
        nginx \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-nginx --role webdevops-php-nginx \
    && /opt/docker/bin/bootstrap.sh

RUN rm ius-release.rpm

EXPOSE 80 443
