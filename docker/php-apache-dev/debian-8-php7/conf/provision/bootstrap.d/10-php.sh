#!/usr/bin/env bash

# Configure php-fpm
go-replace --mode=lineinfile --regex \
    -s '^[\s;]*listen[\s]*='         -r 'listen = 0.0.0.0:9000' \
    -s '^[\s;]*access.format[\s]*='  -r 'access.format = "%R - %u %t \"%m %r%Q%q\" %s %f cpu:%C%% mem:%{megabytes}M reqTime:%d"' \
    -- /opt/docker/etc/php/fpm/pool.d/application.conf
