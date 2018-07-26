#!/usr/bin/env bash

PHP_CLEAR_ENV_AVAILABLE=1

if [[ "$(version-compare "$PHP_VERSION" "5.99.999")" == "<" ]]; then
    #############################
    # PHP 5.x
    #############################
    case "$IMAGE_FAMILY" in
        Debian|Ubuntu)
             PHP_ETC_DIR=/etc/php5
             PHP_MAIN_CONF=/etc/php5/fpm/php-fpm.conf
             PHP_POOL_CONF=www.conf
             PHP_POOL_DIR=/etc/php5/fpm/pool.d
             PHP_FPM_BIN=/usr/sbin/php5-fpm
            ;;

        RedHat)
             PHP_MAIN_CONF=/etc/php-fpm.conf
             PHP_POOL_CONF=www.conf
             PHP_POOL_DIR=/etc/php-fpm.d
             PHP_FPM_BIN=/usr/sbin/php-fpm
            ;;

        Alpine)
             PHP_ETC_DIR=/etc/php5
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
             PHP_ETC_DIR=/etc/php/7.0
             PHP_MAIN_CONF=/etc/php/7.0/fpm/php-fpm.conf
             PHP_POOL_CONF=www.conf
             PHP_POOL_DIR=/etc/php/7.0/fpm/pool.d
             PHP_FPM_BIN=/usr/sbin/php-fpm7.0
            ;;

        RedHat)
             PHP_MAIN_CONF=/etc/php-fpm.conf
             PHP_POOL_CONF=www.conf
             PHP_POOL_DIR=/etc/php-fpm.d
             PHP_FPM_BIN=/usr/sbin/php-fpm
            ;;

        Alpine)
             PHP_ETC_DIR=/etc/php7
             PHP_MAIN_CONF=/etc/php7/php-fpm.conf
             PHP_POOL_CONF=www.conf
             PHP_POOL_DIR=/etc/php7/php-fpm.d
             PHP_FPM_BIN=/usr/sbin/php-fpm7
            ;;
    esac
fi
