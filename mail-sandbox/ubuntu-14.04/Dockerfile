#++++++++++++++++++++++++++++++++++++++
# Postfix Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/postfix:latest
MAINTAINER info@webdevops.io
LABEL vendor=WebDevOps.io
LABEL io.webdevops.layout=5
LABEL io.webdevops.version=0.20.0

ENV MAILBOX_USERNAME "sandbox"
ENV MAILBOX_PASSWORD "mail"

# Install tools
RUN /usr/local/bin/apt-install \
    dovecot-core \
    dovecot-imapd


# Deploy scripts/configurations
COPY conf/ /opt/docker/
RUN /opt/docker/bin/control.sh provision.role webdevops-mail-sandbox \
    && /opt/docker/bin/control.sh service.enable postfix \
    && /opt/docker/bin/control.sh service.enable dovecot \
    && /opt/docker/bin/bootstrap.sh

# SMTP
EXPOSE 25 465 587

# IMAP
EXPOSE 143 993
