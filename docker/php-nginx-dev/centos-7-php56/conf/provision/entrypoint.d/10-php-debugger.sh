#!/usr/bin/env bash
#
# Debugger switch
#

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
