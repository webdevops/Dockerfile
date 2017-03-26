#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/mail-sandbox:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-nginx:latest

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=1.5.0

ENV MAILBOX_USERNAME "dev"
ENV MAILBOX_PASSWORD "dev"

COPY conf/ /opt/docker/

# Install services
RUN /usr/local/bin/apt-install \
        dovecot-core \
        dovecot-imapd \
    && /opt/docker/bin/control.sh service.enable postfix \
    && /opt/docker/bin/control.sh service.enable dovecot \
    && /opt/docker/bin/provision add --tag entrypoint webdevops-mail-sandbox \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-mail-sandbox \
    && /opt/docker/bin/bootstrap.sh \
    && /usr/local/bin/docker-image-cleanup

# Install Roundcube + plugins
RUN cd /app \
    && rm -rf * \
    && wget https://github.com/roundcube/roundcubemail/releases/download/1.2.2/roundcubemail-1.2.2-complete.tar.gz \
    && tar xf roundcubemail-1.2.2-complete.tar.gz  --strip-components 1 \
    && rm -f roundcubemail-1.2.2-complete.tar.gz \
    && ls -l  \
    && rm -rf .git installer \
    && ln -s /opt/docker/etc/roundcube/plugins/webdevops_autologin/ plugins/webdevops_autologin \
    && ln -s /opt/docker/etc/roundcube/config.php config/config.inc.php \
    && /usr/local/bin/docker-image-cleanup

EXPOSE 25 465 587 143 993
