#!/usr/bin/env bash

case "$IMAGE_FAMILY" in
    Debian|Ubuntu)
        # Enable mcrypt (if available)
        if [[ -f "${PHP_ETC_DIR}/mods-available/mcrypt.ini" ]]; then
            ln -sf "${PHP_ETC_DIR}/mods-available/mcrypt.ini" "${PHP_ETC_DIR}/cli/conf.d/20-mcrypt.ini"
            ln -sf "${PHP_ETC_DIR}/mods-available/mcrypt.ini" "${PHP_ETC_DIR}/fpm/conf.d/20-mcrypt.ini"
        fi

        # Register webdevops ini
        ln -sf "/opt/docker/etc/php/php.webdevops.ini" "${PHP_ETC_DIR}/cli/conf.d/98-webdevops.ini"
        ln -sf "/opt/docker/etc/php/php.webdevops.ini" "${PHP_ETC_DIR}/fpm/conf.d/98-webdevops.ini"

        # Register custom php ini
        ln -sf "/opt/docker/etc/php/php.ini" "${PHP_ETC_DIR}/cli/conf.d/99-docker.ini"
        ln -sf "/opt/docker/etc/php/php.ini" "${PHP_ETC_DIR}/fpm/conf.d/99-docker.ini"
        ;;

    RedHat)
        # Register webdevops ini
        ln -sf "/opt/docker/etc/php/php.webdevops.ini" "/etc/php.d/zza-webdevops.ini"

        # Register custom php ini
        ln -sf "/opt/docker/etc/php/php.ini"           "/etc/php.d/zzz-docker.ini"
        ;;

    Alpine)
        # Register webdevops ini
        ln -sf "/opt/docker/etc/php/php.webdevops.ini" "${PHP_ETC_DIR}/conf.d/xzza-webdevops.ini"

        # Register custom php ini
        ln -sf "/opt/docker/etc/php/php.ini"           "${PHP_ETC_DIR}/conf.d/xzzz-docker.ini"
    ;;
esac
