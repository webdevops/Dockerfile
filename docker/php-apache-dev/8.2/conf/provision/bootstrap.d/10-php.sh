#!/usr/bin/env bash

# Configure php-fpm
go-replace --mode=lineinfile --regex \
    -s '^[\s;]*access.format[\s]*='  -r 'access.format = "%R - %u %t \"%m %r%Q%q\" %s %f cpu:%C%% mem:%{megabytes}M reqTime:%d"' \
    -- /opt/docker/etc/php/fpm/pool.d/application.conf

# Listen on port 9000 of all addresses: PHP-FPM binds to [::] (IPv6 and IPv4)
# and falls back to 0.0.0.0 if the container has no IPv6
go-replace --mode=line --regex \
    -s '^[\s;]*listen[\s]*=' -r 'listen = 9000' \
    --path=/opt/docker/etc/php/fpm/ \
    --path-pattern='*.conf'
