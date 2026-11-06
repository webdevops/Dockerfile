#!/usr/bin/env bash

IMAGE_FAMILY=$(docker-image-info family)

case "$IMAGE_FAMILY" in
    Debian|Ubuntu)
        apt-install cron
        ;;

    RedHat)
        yum-install cronie
        ;;
esac
