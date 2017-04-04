#!/usr/bin/env bash

# Installation
case "$IMAGE_FAMILY" in
    Debian|Ubuntu)
        apt-install dnsmasq
        ;;

    RedHat)
        yum-install dnsmasq
        ;;

    Alpine)
        apk-install dnsmasq
        ;;
esac

# Configuration
go-replace \
    -s '^[\s]*user[\s]*=' \
    -r 'user = root' \
    --replace-line
    /etc/dnsmasq.conf

go-replace \
    -s '^[\s]*conf-dir[\s]*=' \
    -r 'conf-dir = /etc/dnsmasq.d' \
    --replace-line
    /etc/dnsmasq.conf
