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

# remote debugger
phpEnvironmentVariable "xdebug.remote_connect_back" "XDEBUG_REMOTE_CONNECT_BACK"
phpEnvironmentVariable "xdebug.remote_autostart"    "XDEBUG_REMOTE_AUTOSTART"
phpEnvironmentVariable "xdebug.remote_host"         "XDEBUG_REMOTE_HOST"
phpEnvironmentVariable "xdebug.remote_port"         "XDEBUG_REMOTE_PORT"
phpEnvironmentVariable "xdebug.max_nesting_level"   "XDEBUG_MAX_NESTING_LEVEL"
phpEnvironmentVariable "xdebug.idekey"              "XDEBUG_IDE_KEY"

# profiler
phpEnvironmentVariable "xdebug.profiler_enable"               "XDEBUG_PROFILER_ENABLE"
phpEnvironmentVariable "xdebug.profiler_enable_trigger"       "XDEBUG_PROFILER_ENABLE_TRIGGER"
phpEnvironmentVariable "xdebug.profiler_enable_trigger_value" "XDEBUG_PROFILER_ENABLE_TRIGGER_VALUE"
phpEnvironmentVariable "xdebug.profiler_output_dir"           "XDEBUG_PROFILER_OUTPUT_DIR"
phpEnvironmentVariable "xdebug.profiler_output_name"          "XDEBUG_PROFILER_OUTPUT_NAME"

###################
# BLACKFIRE
###################
phpEnvironmentVariable "blackfire.server_id"    "BLACKFIRE_SERVER_ID"
phpEnvironmentVariable "blackfire.server_token" "BLACKFIRE_SERVER_TOKEN"
