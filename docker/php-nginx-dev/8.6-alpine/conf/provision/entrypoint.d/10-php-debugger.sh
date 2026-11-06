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
