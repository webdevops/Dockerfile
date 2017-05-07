#!/usr/bin/env bash

# Link main php-fpm binary
ln -sf -- "$PHP_FPM_BIN" /usr/local/bin/php-fpm

# Move php-fpm main file to /opt/docker/etc/php/fpm/ and create symlink
mv -- "$PHP_MAIN_CONF" /opt/docker/etc/php/fpm/php-fpm.conf
ln -sf -- /opt/docker/etc/php/fpm/php-fpm.conf "$PHP_MAIN_CONF"

# Configure php-fpm main (all versions)
go-replace --mode=lineinfile --regex \
    -s '^[\s;]*error_log[\s]*=' -r 'error_log = /docker.stderr' \
    -s '^[\s;]*pid[\s]*='       -r 'pid = /var/run/php-fpm.pid' \
    -- /opt/docker/etc/php/fpm/php-fpm.conf

if [[ "$(version-compare "$PHP_VERSION" "5.99.999")" == "<" ]]; then
    # Configure php-fpm main (php 5.x)
    go-replace --mode=lineinfile --regex \
        -s '^[\s;]*daemonize[\s]*=' -r 'daemonize = no' \
        -- /opt/docker/etc/php/fpm/php-fpm.conf
fi
