#!/usr/bin/env bash

# Configure php-fpm
go-replace --mode=lineinfile --regex \
    -s '^[\s;]*access.format[\s]*='  -r 'access.format = "%R - %u %t \"%m %r%Q%q\" %s %f cpu:%C%% mem:%{megabytes}M reqTime:%d"' \
    -- /opt/docker/etc/php/fpm/pool.d/application.conf

# Listen on all IPv6 and IPv4 addresses. Without IPv6 in the container,
# entrypoint.d/20-php-fpm.sh switches to 0.0.0.0:9000 at start.
go-replace --mode=line --regex \
    -s '^[\s;]*listen[\s]*=' -r 'listen = [::]:9000' \
    --path=/opt/docker/etc/php/fpm/ \
    --path-pattern='*.conf'
