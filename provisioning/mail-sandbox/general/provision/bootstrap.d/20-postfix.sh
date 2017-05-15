#!/usr/bin/env bash

# Create empty recipient_canonical_maps
touch /etc/postfix/recipient_canonical_maps

# Configuration
go-replace --mode=lineinfile --regex \
    -s '^[\s#]*smtpd_banner[\s]*=' -r 'smtpd_banner = myhostname ESMTP' \
    -s '^[\s#]*inet_interfaces[\s]*=' -r 'inet_interfaces = all' \
    -s '^[\s#]*inet_protocols[\s]*=' -r 'inet_protocols = ipv4' \
    -s '^[\s#]*home_mailbox[\s]*=' -r 'home_mailbox = .mail/' \
    -s '^[\s#]*mynetworks[\s]*=' -r 'mynetworks = 127.0.0.0/8 [::1]/128 0.0.0.0/0 [::1]/0' \
    -s '^[\s#]*mydestination[\s]*=' -r 'mydestination = localhost' \
    -s '^[\s#]*message_size_limit[\s]*=' -r 'message_size_limit = 102400000' \
    -s '^[\s#]*recipient_canonical_maps[\s]*=' -r 'recipient_canonical_maps = regexp:/etc/postfix/recipient_canonical_maps' \
    -- /etc/postfix/main.cf

# Setup listening on port 1025
echo "1025      inet  n       -       y       -       -       smtpd" >> /etc/postfix/master.cf
