#!/usr/bin/env bash

# Restrict php-fpm to local connection
go-replace --mode=line --regex \
    -s '^[\s;]*listen[\s]*='  -r 'listen = 127.0.0.1:9000' \
        --path=/opt/docker/etc/php/fpm/ \
        --path-pattern='*.conf'
