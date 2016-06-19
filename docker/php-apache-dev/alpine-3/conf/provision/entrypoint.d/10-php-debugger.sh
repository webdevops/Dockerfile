#
# Debugger switch
#

function phpModuleEnable() {
    for PHP_FILE in $*; do
        if [[ -f "$PHP_FILE" ]]; then
            pysed -r '^[; ]*((zend_)?(extension=.*))$' '\1' "$PHP_FILE" --write
        fi
    done
}

function phpModuleDisable() {
    for PHP_FILE in $*; do
        if [[ -f "$PHP_FILE" ]]; then
            pysed -r '^[; ]*((zend_)?(extension=.*))$' ';\1' "$PHP_FILE" --write
        fi
    done
}

PHP_XDEBUG_FILES="
/etc/php.d/xdebug.ini
/etc/php5/mods-available/xdebug.ini
/etc/php5/cli/conf.d/20-xdebug.ini
/etc/php5/cli/conf.d/xdebug.ini
/etc/php5/fpm/conf.d/20-xdebug.ini
/etc/php5/fpm/conf.d/xdebug.ini
/etc/php/7.0/mods-available/xdebug.ini
/etc/php/7.0/cli/conf.d/20-xdebug.ini
/etc/php/7.0/fpm/conf.d/20-xdebug.ini"

PHP_BLACKFIRE_FILES="
/etc/php.d/zz-blackfire.ini
/etc/php5/conf.d/90-blackfire.ini
/etc/php5/conf.d/zz-blackfire.ini
/etc/php5/cli/conf.d/90-blackfire.ini
/etc/php5/cli/conf.d/zz-blackfire.ini
/etc/php5/fpm/conf.d/90-blackfire.ini
/etc/php5/fpm/conf.d/zz-blackfire.ini
/etc/php/7.0/cli/conf.d/90-blackfire.ini
/etc/php/7.0/fpm/conf.d/90-blackfire.ini"

if [[ -n "${PHP_DEBUGGER+x}" ]]; then
    case "$PHP_DEBUGGER" in
        xdebug)
            echo "PHP-Debugger: Xdebug enabled"
            ;;

        blackfire)
            echo "PHP-Debugger: Blackfire enabled"
            phpModuleEnable $PHP_BLACKFIRE_FILES
            phpModuleDisable $PHP_XDEBUG_FILES
            ;;

        none)
            echo "PHP-Debugger: none"
            phpModuleDisable $PHP_BLACKFIRE_FILES
            phpModuleDisable $PHP_XDEBUG_FILES
            ;;
    esac
fi
