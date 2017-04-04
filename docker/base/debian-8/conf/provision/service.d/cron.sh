#!/usr/bin/env bash

case "$IMAGE_FAMILY" in
    Debian|Ubuntu)
        apt-install cron
        ;;

    RedHat)
        yum-install cronie
        ;;
esac
