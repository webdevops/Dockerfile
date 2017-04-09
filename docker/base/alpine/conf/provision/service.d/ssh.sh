#!/usr/bin/env bash

case "$IMAGE_FAMILY" in
    Debian|Ubuntu)
        apt-install openssh-server
        ;;

    RedHat)
        yum-install openssh-server
        ;;

    Alpine)
        apk-install openssh
        ;;
esac
