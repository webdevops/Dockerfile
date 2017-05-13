#!/usr/bin/env bash

# setup mailname
hostname > /etc/mailname

# Create recipient_canonical_maps (redirect mails to local sandbox)
echo "/^.*$/  ${MAILBOX_USERNAME}@localhost" > /etc/postfix/recipient_canonical_maps
chown root:root /etc/postfix/recipient_canonical_maps
chmod 0644 /etc/postfix/recipient_canonical_maps
postmap /etc/postfix/recipient_canonical_maps
