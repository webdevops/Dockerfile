#!/usr/bin/env bash

# Installation
case "$IMAGE_FAMILY" in
    Debian|Ubuntu)
        apt-install postfix
        ;;

    RedHat)
        yum-install postfix
        ;;

    Alpine)
        apk-install postfix
        ln -s -f /etc/postfix/aliases /etc/aliases
        ;;
esac

# Configuration
go-replace \
    -s '^[\s]*mydestination[\s]*=' \
    -r 'mydestination = ' \
    --replace-line \
    /etc/postfix/main.cf

go-replace \
    -s '^[\s]*message_size_limit[\s]*=' \
    -r 'message_size_limit = 15240000' \
    --replace-line \
    /etc/postfix/main.cf

go-replace \
    -s '^[\s]*smtp_use_tls[\s]*=' \
    -r 'smtp_use_tls = yes' \
    --replace-line \
    /etc/postfix/main.cf

go-replace \
    -s '^[\s]*smtp_tls_security_level[\s]*=' \
    -r 'smtp_tls_security_level = may' \
    --replace-line \
    /etc/postfix/main.cf

# Remove hostname
go-replace \
    -s '^[\s]*myhostname[\s]*=' \
    -r '# myhostname' \
    --replace-line \
    /etc/postfix/main.cf
