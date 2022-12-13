#!/usr/bin/env bash

IMAGE_FAMILY=$(docker-image-info family)

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
go-replace --mode=line \
    -s '^[\s]*user[\s]*=' -r 'user = root' \
    -s '^[\s]*conf-dir[\s]*=' -r 'conf-dir = /etc/dnsmasq.d' \
    -- /etc/dnsmasq.conf
