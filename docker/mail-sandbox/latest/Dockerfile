#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/mail-sandbox:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/php-nginx:latest

ENV MAILBOX_USERNAME="dev" \
    MAILBOX_PASSWORD="dev"

COPY conf/ /opt/docker/

RUN set -x \
    # Install services
    && apt-install \
        dovecot-core \
        dovecot-imapd \
    && docker-service enable postfix \
    && docker-service enable dovecot \
    && docker-run-bootstrap \
    && docker-image-cleanup

RUN set -x \
    # Install Roundcube + plugins
    && cd /app \
    && rm -rf * \
    && wget https://github.com/roundcube/roundcubemail/releases/download/1.2.2/roundcubemail-1.2.2-complete.tar.gz \
    && tar xf roundcubemail-1.2.2-complete.tar.gz  --strip-components 1 \
    && rm -f roundcubemail-1.2.2-complete.tar.gz \
    && ls -l  \
    && rm -rf .git installer \
    && ln -s /opt/docker/etc/roundcube/plugins/webdevops_autologin/ plugins/webdevops_autologin \
    && ln -s /opt/docker/etc/roundcube/config.php config/config.inc.php

EXPOSE 25 465 587 143 993
