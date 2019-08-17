#!/usr/bin/env bash

PHP_CLEAR_ENV_AVAILABLE=1

if [[ "$(version-compare "$PHP_VERSION" "5.99.999")" == "<" ]]; then
    #############################
    # PHP 5.x
    #############################
    case "$IMAGE_FAMILY" in
        Debian|Ubuntu)
             PHP_ETC_DIR=/etc/php5
             if [[ -d "/etc/php5/mods-available" ]]; then
                PHP_MOD_INI_DIR=/etc/php5/mods-available
             else
                PHP_MOD_INI_DIR=/etc/php5/conf.d
             fi
             PHP_MAIN_CONF=/etc/php5/fpm/php-fpm.conf
             PHP_POOL_CONF=www.conf
             PHP_POOL_DIR=/etc/php5/fpm/pool.d
             PHP_FPM_BIN=/usr/sbin/php5-fpm
            ;;

        RedHat)
             PHP_ETC_DIR=/etc/php.d
             PHP_MOD_INI_DIR=/etc/php.d
             PHP_MAIN_CONF=/etc/php-fpm.conf
             PHP_POOL_CONF=www.conf
             PHP_POOL_DIR=/etc/php-fpm.d
             PHP_FPM_BIN=/usr/sbin/php-fpm
            ;;

        Alpine)
             PHP_ETC_DIR=/etc/php5
             PHP_MOD_INI_DIR=/etc/php5/conf.d
             PHP_MAIN_CONF=/etc/php5/php-fpm.conf
             PHP_POOL_CONF=www.conf
             PHP_POOL_DIR=/etc/php5/fpm.d
             PHP_FPM_BIN=/usr/bin/php-fpm5
            ;;
    esac

    # Check for claer env setting (not available in old versions)
    if [[ "$(version-compare "$PHP_VERSION" "5.4.0")" == "<" ]]; then
        PHP_CLEAR_ENV_AVAILABLE=0
    fi

    if [[ "$(version-compare "$PHP_VERSION" "5.4.*")" == "=" ]] && [[ "$(version-compare "$PHP_VERSION" "5.4.27")" == "<" ]]; then
        PHP_CLEAR_ENV_AVAILABLE=0
    fi

    # Check for claer env setting (not available in old versions)
    if [[ "$(version-compare "$PHP_VERSION" "5.5.*")" == "=" ]] && [[ "$(version-compare "$PHP_VERSION" "5.5.11")" == "<" ]]; then
        PHP_CLEAR_ENV_AVAILABLE=0
    fi

elif [[ "$(version-compare "$PHP_VERSION" "7.99.999")" == "<" ]]; then
    #############################
    # PHP 7.x
    #############################
    case "$IMAGE_FAMILY" in
        Debian|Ubuntu)
             if [[ "$(version-compare "$PHP_VERSION" "7.4.*")" == "=" ]]; then
                 PHP_ETC_DIR=/etc/php/7.4
                 if [[ -d "/etc/php/7.4/mods-available" ]]; then
                     PHP_MOD_INI_DIR=/etc/php/7.4/mods-available
                 else
                     PHP_MOD_INI_DIR=/etc/php/7.4/conf.d
                 fi
                 PHP_MAIN_CONF=/etc/php/7.4/fpm/php-fpm.conf
                 PHP_POOL_DIR=/etc/php/7.4/fpm/pool.d
                 PHP_FPM_BIN=/usr/sbin/php-fpm7.4
             elif [[ "$(version-compare "$PHP_VERSION" "7.3.*")" == "=" ]]; then
                 PHP_ETC_DIR=/etc/php/7.3
                 if [[ -d "/etc/php/7.3/mods-available" ]]; then
                     PHP_MOD_INI_DIR=/etc/php/7.3/mods-available
                 else
                     PHP_MOD_INI_DIR=/etc/php/7.3/conf.d
                 fi
                 PHP_MAIN_CONF=/etc/php/7.3/fpm/php-fpm.conf
                 PHP_POOL_DIR=/etc/php/7.3/fpm/pool.d
                 PHP_FPM_BIN=/usr/sbin/php-fpm7.3
             elif [[ "$(version-compare "$PHP_VERSION" "7.2.*")" == "=" ]]; then
                 PHP_ETC_DIR=/etc/php/7.2
                 if [[ -d "/etc/php/7.2/mods-available" ]]; then
                     PHP_MOD_INI_DIR=/etc/php/7.2/mods-available
                 else
                     PHP_MOD_INI_DIR=/etc/php/7.2/conf.d
                 fi
                 PHP_MAIN_CONF=/etc/php/7.2/fpm/php-fpm.conf
                 PHP_POOL_DIR=/etc/php/7.2/fpm/pool.d
                 PHP_FPM_BIN=/usr/sbin/php-fpm7.2
             elif [[ "$(version-compare "$PHP_VERSION" "7.1.*")" == "=" ]]; then
                 PHP_ETC_DIR=/etc/php/7.1
                 if [[ -d "/etc/php/7.1/mods-available" ]]; then
                     PHP_MOD_INI_DIR=/etc/php/7.1/mods-available
                 else
                     PHP_MOD_INI_DIR=/etc/php/7.1/conf.d
                 fi
                 PHP_MAIN_CONF=/etc/php/7.1/fpm/php-fpm.conf
                 PHP_POOL_DIR=/etc/php/7.1/fpm/pool.d
                 PHP_FPM_BIN=/usr/sbin/php-fpm7.1
             else
                 PHP_ETC_DIR=/etc/php/7.0
                 if [[ -d "/etc/php/7.0/mods-available" ]]; then
                     PHP_MOD_INI_DIR=/etc/php/7.0/mods-available
                 else
                     PHP_MOD_INI_DIR=/etc/php/7.0/conf.d
                 fi
                 PHP_MAIN_CONF=/etc/php/7.0/fpm/php-fpm.conf
                 PHP_POOL_DIR=/etc/php/7.0/fpm/pool.d
                 PHP_FPM_BIN=/usr/sbin/php-fpm7.0
             fi
             PHP_POOL_CONF=www.conf
            ;;

        RedHat)
             PHP_ETC_DIR="/etc/php.d"
             PHP_MOD_INI_DIR=/etc/php.d
             PHP_MAIN_CONF=/etc/php-fpm.conf
             PHP_POOL_CONF=www.conf
             PHP_POOL_DIR=/etc/php-fpm.d
             PHP_FPM_BIN=/usr/sbin/php-fpm
            ;;

        Alpine)
             PHP_ETC_DIR=/etc/php7
             PHP_MOD_INI_DIR=/etc/php7/conf.d
             PHP_MAIN_CONF=/etc/php7/php-fpm.conf
             PHP_POOL_CONF=www.conf
             PHP_POOL_DIR=/etc/php7/php-fpm.d
             PHP_FPM_BIN=/usr/sbin/php-fpm7
            ;;
    esac
fi
