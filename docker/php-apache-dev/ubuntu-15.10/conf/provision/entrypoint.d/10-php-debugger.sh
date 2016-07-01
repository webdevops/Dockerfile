#!/usr/bin/env bash

#################################################
# Debugger switch
#################################################

PHP_CONF_PATHS="
/etc/php5/conf.d
/etc/php7/conf.d
/etc/php.d
/etc/php5/mods-available
/etc/php5/cli/conf.d
/etc/php5/cli/conf.d
/etc/php5/fpm/conf.d
/etc/php5/fpm/conf.d
/etc/php/7.0/mods-available
/etc/php/7.0/cli/conf.d
/etc/php/7.0/fpm/conf.d"

function phpModuleRemove() {
    if [ "$#" -ne 1 ]; then
        echo "You must specify the name of the PHP module which you want to disable"
        exit 1
    fi

    echo " - Removing PHP module ${1}"
    for CONF_PATH in $PHP_CONF_PATHS; do
        rm -f "${CONF_PATH}"/*"${1}".ini
    done
}


if [[ -n "${PHP_DEBUGGER+x}" ]]; then
    case "$PHP_DEBUGGER" in
        xdebug)
            echo "PHP-Debugger: Xdebug enabled"
            phpModuleRemove "blackfire"
            ;;

        blackfire)
            echo "PHP-Debugger: Blackfire enabled"
            phpModuleRemove "xdebug"
            /opt/docker/bin/control.sh service.enable blackfire-agent
            ;;

        none)
            echo "PHP-Debugger: none"
            phpModuleRemove "blackfire"
            phpModuleRemove "xdebug"
            ;;
    esac

else

    echo "PHP-Debugger: not specified - default is xdebug"
    phpModuleRemove "blackfire"

fi

#################################################
# PHP debugger environment variables
#################################################

function phpEnvironmentVariable() {
    PHP_ENV_NAME="$1"
    PHP_ENV_VALUE="$2"

    echo "${PHP_ENV_NAME}=\"${PHP_ENV_VALUE}\"" >> /opt/docker/etc/php/php.ini
}

###################
# XDEBUG
###################

# xdebug.remote_connect_back
if [[ -n "${XDEBUG_REMOTE_CONNECT_BACK+x}" ]]; then
    phpEnvironmentVariable "xdebug.remote_connect_back" "$XDEBUG_REMOTE_CONNECT_BACK"
fi

# xdebug.remote_autostart
if [[ -n "${XDEBUG_REMOTE_AUTOSTART+x}" ]]; then
    phpEnvironmentVariable "xdebug.remote_autostart" "$XDEBUG_REMOTE_AUTOSTART"
fi

# xdebug.remote_host
if [[ -n "${XDEBUG_REMOTE_HOST+x}" ]]; then
    phpEnvironmentVariable "xdebug.remote_host" "$XDEBUG_REMOTE_HOST"
fi

# xdebug.remote_port
if [[ -n "${XDEBUG_REMOTE_PORT+x}" ]]; then
    phpEnvironmentVariable "xdebug.remote_port" "$XDEBUG_REMOTE_PORT"
fi

###################
# BLACKFIRE
###################

# blackfire.server_id
if [[ -n "${BLACKFIRE_SERVER_ID+x}" ]]; then
    phpEnvironmentVariable "blackfire.server_id" "$BLACKFIRE_SERVER_ID"
fi

# blackfire.server_token
if [[ -n "${BLACKFIRE_SERVER_TOKEN+x}" ]]; then
    phpEnvironmentVariable "blackfire.server_token" "$BLACKFIRE_SERVER_TOKEN"
fi
