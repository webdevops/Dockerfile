#!/usr/bin/env bash

IMAGE_FAMILY=$(docker-image-info family)

# Installation
case "$IMAGE_FAMILY" in
    Debian|Ubuntu)
        apt-install postfix
        ;;

    RedHat)
        yum-install postfix

        # Fix mysql lib
        if [[ ! -f /lib64/libmysqlclient.so.18 ]] && [[ -f /usr/lib64/mysql/libmysqlclient.so.18 ]]; then
            ln -s /usr/lib64/mysql/libmysqlclient.so.18 /lib64/libmysqlclient.so.18
        fi
        ;;

    Alpine)
        apk-install postfix
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
