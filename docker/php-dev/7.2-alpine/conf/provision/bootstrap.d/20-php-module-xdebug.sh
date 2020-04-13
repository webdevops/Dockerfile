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
go-replace --mode=lineinfile --regex \
    -s '^[\s;]*xdebug.remote_enable[\s]*='            -r 'xdebug.remote_enable = 1' \
    -s '^[\s;]*xdebug.remote_connect_back[\s]*='      -r 'xdebug.remote_connect_back = 1' \
    -s '^[\s;]*xdebug.idekey[\s]*='                   -r 'xdebug.idekey = docker' \
    -s '^[\s;]*xdebug.cli_color[\s]*='                -r 'xdebug.cli_color = 1' \
    -s '^[\s;]*xdebug.max_nesting_level[\s]*='        -r 'xdebug.max_nesting_level = 1000' \
    -s '^[\s;]*xdebug.profiler_enable_trigger[\s]*='  -r 'xdebug.profiler_enable_trigger = 1000' \
    -s '^[\s;]*xdebug.profiler_output_dir[\s]*='      -r 'xdebug.profiler_output_dir = /tmp/debug' \
    -s '^[\s;]*xdebug.output_dir[\s]*='               -r 'xdebug.output_dir = /tmp/debug' \
    -- /opt/docker/etc/php/php.webdevops.ini
