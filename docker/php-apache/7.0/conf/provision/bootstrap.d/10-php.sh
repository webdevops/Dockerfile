#!/usr/bin/env bash

# Restrict php-fpm to local connection
go-replace --mode=lineinfile --regex \
    -s '^[\s;]*listen[\s]*='  -r 'listen = 127.0.0.1:9000' \
    -- /opt/docker/etc/php/fpm/pool.d/application.conf \
       /opt/docker/etc/php/fpm/php-fpm.conf
