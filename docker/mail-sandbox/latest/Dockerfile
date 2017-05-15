#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/mail-sandbox:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-nginx:latest

ENV MAILBOX_USERNAME="dev" \
    MAILBOX_PASSWORD="dev"

COPY conf/ /opt/docker/

# Install services
RUN /usr/local/bin/apt-install \
        dovecot-core \
        dovecot-imapd \
    && /opt/docker/bin/control.sh service.enable postfix \
    && /opt/docker/bin/control.sh service.enable dovecot \
    && /opt/docker/bin/bootstrap.sh \
    && docker-image-cleanup

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
    && docker-image-cleanup

EXPOSE 25 465 587 143 993
