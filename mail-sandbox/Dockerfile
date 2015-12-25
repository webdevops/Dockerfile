#++++++++++++++++++++++++++++++++++++++
# Postfix Docker container
#++++++++++++++++++++++++++++++++++++++

FROM webdevops/postfix:latest
MAINTAINER info@webdevops.io

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
EXPOSE 25
EXPOSE 465
EXPOSE 587

# IMAP
EXPOSE 143
EXPOSE 993
