#!/usr/bin/env bash

IMAGE_FAMILY=$(docker-image-info family)

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
go-replace --mode=line \
    -s '^[\s]*mydestination[\s]*=' -r 'mydestination = ' \
    -s '^[\s]*message_size_limit[\s]*=' -r 'message_size_limit = 15240000' \
    -s '^[\s]*smtp_use_tls[\s]*=' -r 'smtp_use_tls = yes' \
    -s '^[\s]*smtp_tls_security_level[\s]*=' -r 'smtp_tls_security_level = may' \
    -s '^[\s]*myhostname[\s]*=' -r '# myhostname' \
    -- /etc/postfix/main.cf
