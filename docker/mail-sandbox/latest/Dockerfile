#+++++++++++++++++++++++++++++++++++++++
# Dockerfile for webdevops/mail-sandbox:latest
#    -- automatically generated  --
#+++++++++++++++++++++++++++++++++++++++

FROM webdevops/postfix:latest

MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=8
LABEL io.webdevops.version=0.54.0

ENV MAILBOX_USERNAME "sandbox"
ENV MAILBOX_PASSWORD "mail"

COPY conf/ /opt/docker/

# Install services
RUN /usr/local/bin/apt-install \
        dovecot-core \
        dovecot-imapd \
    && /opt/docker/bin/control.sh service.enable postfix \
    && /opt/docker/bin/control.sh service.enable dovecot \
    && /opt/docker/bin/provision run --tag bootstrap --role webdevops-mail-sandbox \
    && /opt/docker/bin/bootstrap.sh


EXPOSE 25 465 587 143 993
