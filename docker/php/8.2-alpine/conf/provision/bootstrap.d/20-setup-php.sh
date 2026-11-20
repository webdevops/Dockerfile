#!/usr/bin/env bash

case "$IMAGE_FAMILY" in
    Debian|Ubuntu|Alpine)
        # Register webdevops ini
        ln -sf "/opt/docker/etc/php/php.webdevops.ini" "${PHP_ETC_DIR}/conf.d/98-webdevops.ini"

        # Register custom php ini
        ln -sf "/opt/docker/etc/php/php.ini" "${PHP_ETC_DIR}/conf.d/99-docker.ini"
        ;;
esac
