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
/etc/php/7.1/mods-available
/etc/php/7.2/mods-available
/etc/php/7.3/mods-available
/etc/php/7.4/mods-available
/etc/php/7.0/cli/conf.d
/etc/php/7.1/cli/conf.d
/etc/php/7.2/cli/conf.d
/etc/php/7.3/cli/conf.d
/etc/php/7.4/cli/conf.d
/etc/php/7.0/fpm/conf.d
/etc/php/7.1/fpm/conf.d
/etc/php/7.2/fpm/conf.d
/etc/php/7.3/fpm/conf.d
/etc/php/7.4/fpm/conf.d
/usr/local/etc/php/conf.d/"

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
            docker-service-enable blackfire-agent
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
    PHP_INI_KEY="$1"
    PHP_ENV_NAME="$2"

    if [[ -n "${!PHP_ENV_NAME+x}" ]]; then
        PHP_ENV_VALUE="${!PHP_ENV_NAME}"
        echo "${PHP_INI_KEY}=\"${PHP_ENV_VALUE}\"" >> /opt/docker/etc/php/php.ini
    fi
}

###################
# XDEBUG
###################

# xdebug3 remote debugger
phpEnvironmentVariable "xdebug.discover_client_host" "XDEBUG_DISCOVER_CLIENT_HOST"
phpEnvironmentVariable "xdebug.mode"                 "XDEBUG_MODE"
phpEnvironmentVariable "xdebug.start_with_request"   "XDEBUG_START_WITH_REQUEST"
phpEnvironmentVariable "xdebug.client_host"          "XDEBUG_CLIENT_HOST"
phpEnvironmentVariable "xdebug.client_port"          "XDEBUG_CLIENT_PORT"

# xdebug3 profiler
phpEnvironmentVariable "xdebug.trigger_value"       "XDEBUG_TRIGGER_VALUE"
phpEnvironmentVariable "xdebug.output_dir"          "XDEBUG_OUTPUT_DIR"

###################
# BLACKFIRE
###################
phpEnvironmentVariable "blackfire.server_id"    "BLACKFIRE_SERVER_ID"
phpEnvironmentVariable "blackfire.server_token" "BLACKFIRE_SERVER_TOKEN"
