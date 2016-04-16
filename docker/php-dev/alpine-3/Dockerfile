#++++++++++++++++++++++++++++++++++++++
# Alpine 3 PHP Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/php:alpine-3
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=6
LABEL io.webdevops.version=0.23.0

# Install tools
RUN /usr/local/bin/apk-install \
    graphviz 

COPY conf/ /opt/docker/
RUN bash /opt/docker/bin/control.sh provision.role.bootstrap webdevops-php-dev \
    && bash /opt/docker/bin/bootstrap.sh

EXPOSE 9000

CMD ["supervisord"]
