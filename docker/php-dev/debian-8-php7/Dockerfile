#++++++++++++++++++++++++++++++++++++++
# Debian 8 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:debian-8
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0

# Install dotdeb
RUN echo "deb http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list \
    && echo "deb-src http://packages.dotdeb.org jessie all" >> /etc/apt/sources.list \
    && wget wget -O- https://www.dotdeb.org/dotdeb.gpg | apt-key add  -

# Install tools
RUN /usr/local/bin/apt-install \
    graphviz 

COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-php-dev \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 9000

CMD ["supervisord"]