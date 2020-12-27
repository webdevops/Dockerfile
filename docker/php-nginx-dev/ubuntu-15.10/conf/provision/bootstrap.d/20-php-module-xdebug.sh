#!/usr/bin/env bash

IMAGE_FAMILY=$(docker-image-info family)

case "$IMAGE_FAMILY" in
    Alpine)
        # Fix php xdebug module [Alpine family]

        if [[ -f "/etc/php5/conf.d/xdebug.ini" ]]; then
            go-replace --mode=lineinfile \
                -s '^extension=xdebug.so'  -r 'zend_extension=xdebug.so' \
                /etc/php5/conf.d/xdebug.ini
        fi

        if [[ -f "/etc/php7/conf.d/xdebug.ini" ]]; then
            go-replace --mode=lineinfile \
                -s '^extension=xdebug.so'  -r 'zend_extension=xdebug.so' \
                /etc/php7/conf.d/xdebug.ini
        fi

        ;;
esac

# Configure xdebug for development
declare -A XDEBUG
XDEBUG[version]=$(php -r "echo phpversion('xdebug');")

if [[ "$(version-compare "${XDEBUG[version]}" "3.0.0")" == "<" ]]; then
    XDEBUG[remote_enable]='xdebug.remote_enable = 1'
    XDEBUG[remote_connect_back]='xdebug.remote_connect_back = 1'
    XDEBUG[profiler_output_dir]='xdebug.profiler_output_dir = /tmp/debug'
else
    XDEBUG[mode]='xdebug.mode = debug'
    XDEBUG[discover_client_host]='xdebug.discover_client_host = 1'
    XDEBUG[start_with_request]='xdebug.start_with_request = trigger'
    XDEBUG[output_dir]='xdebug.output_dir = /tmp/debug'
fi

go-replace --mode=lineinfile --regex \
    -s '^[\s;]*xdebug.idekey[\s]*='                   -r 'xdebug.idekey = docker' \
    -s '^[\s;]*xdebug.cli_color[\s]*='                -r 'xdebug.cli_color = 1' \
    -s '^[\s;]*xdebug.max_nesting_level[\s]*='        -r 'xdebug.max_nesting_level = 1000' \
    -s '^[\s;]*xdebug.remote_enable[\s]*='            -r "${XDEBUG[remote_enable]:-}" \
    -s '^[\s;]*xdebug.remote_connect_back[\s]*='      -r "${XDEBUG[remote_connect_back]:-}" \
    -s '^[\s;]*xdebug.profiler_output_dir[\s]*='      -r "${XDEBUG[profiler_output_dir]:-}" \
    -s '^[\s;]*xdebug.mode[\s]*='                     -r "${XDEBUG[mode]:-}" \
    -s '^[\s;]*xdebug.discover_client_host[\s]*='     -r "${XDEBUG[discover_client_host]:-}" \
    -s '^[\s;]*xdebug.start_with_request[\s]*='       -r "${XDEBUG[start_with_request]:-}" \
    -s '^[\s;]*xdebug.output_dir[\s]*='               -r "${XDEBUG[output_dir]:-}" \
    -- /opt/docker/etc/php/php.webdevops.ini
