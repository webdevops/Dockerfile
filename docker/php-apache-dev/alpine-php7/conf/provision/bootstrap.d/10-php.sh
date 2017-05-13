#!/usr/bin/env bash

PHP_VERSION=$(php -r 'echo phpversion();' | cut -d '-' -f 1)

# Configure php-fpm
go-replace --mode=lineinfile --regex \
    -s '^[\s;]*access.format[\s]*='  -r 'access.format = "%R - %u %t \"%m %r%Q%q\" %s %f cpu:%C%% mem:%{megabytes}M reqTime:%d"' \
    -- /opt/docker/etc/php/fpm/pool.d/application.conf

if [[ "$(version-compare "$PHP_VERSION" "5.5.999")" == "<" ]]; then
    # listen on public IPv4 port
    # no ipv6 sockets available for old php version
    go-replace --mode=line --regex \
        -s '^[\s;]*listen[\s]*=' -r 'listen = 0.0.0.0:9000' \
        -- /opt/docker/etc/php/fpm/pool.d/application.conf \
           /opt/docker/etc/php/fpm/php-fpm.conf
else
    # listen on public IPv6 port
    go-replace --mode=line --regex \
        -s '^[\s;]*listen[\s]*=' -r 'listen = [::]:9000' \
        -- /opt/docker/etc/php/fpm/pool.d/application.conf \
           /opt/docker/etc/php/fpm/php-fpm.conf

fi
