#!/usr/bin/env bash

#################################################
# Debugger switch
#################################################

PHP_CONF_PATHS="/usr/local/etc/php/conf.d/"

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
            # the agent of the Blackfire CLI ends at once without the server credentials
            if [[ -n "${BLACKFIRE_SERVER_ID:-}" && -n "${BLACKFIRE_SERVER_TOKEN:-}" ]]; then
                docker-service-enable blackfire-agent
            else
                echo "PHP-Debugger: Blackfire agent not started, BLACKFIRE_SERVER_ID and BLACKFIRE_SERVER_TOKEN are not set;"
                echo "              set them, or set php.blackfire.agent_socket to an agent of your own"
            fi
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
