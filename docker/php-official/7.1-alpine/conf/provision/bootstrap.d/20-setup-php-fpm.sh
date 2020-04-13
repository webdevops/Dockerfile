#!/usr/bin/env bash

# Link main php-fpm binary
ln -sf -- "$PHP_FPM_BIN" /usr/local/bin/php-fpm

# Move php-fpm main file to /opt/docker/etc/php/fpm/ and create symlink
if [[ ! -f /opt/docker/etc/php/fpm/php-fpm.conf ]]; then
       mv -- "$PHP_MAIN_CONF" /opt/docker/etc/php/fpm/php-fpm.conf
else
       rm -f -- "PHP_MAIN_CONF"
fi
ln -sf -- /opt/docker/etc/php/fpm/php-fpm.conf "$PHP_MAIN_CONF"

# Configure php-fpm main (all versions)
go-replace --mode=lineinfile --regex \
    --lineinfile-after='\[global\]' \
    -s '^[\s;]*error_log[\s]*=' -r 'error_log = /docker.stderr' \
    -s '^[\s;]*pid[\s]*='       -r 'pid = /var/run/php-fpm.pid' \
    -- /opt/docker/etc/php/fpm/php-fpm.conf

if [[ "$(version-compare "$PHP_VERSION" "5.5.999")" == "<" ]]; then
    # listen on public IPv4 port
    # no ipv6 sockets available for old php version
    go-replace --mode=line --regex \
        -s '^[\s;]*listen[\s]*=' -r 'listen = 0.0.0.0:9000' \
        --path=/opt/docker/etc/php/fpm/ \
        --path-pattern='*.conf'
else
    # listen on public IPv6 port
    go-replace --mode=line --regex \
        -s '^[\s;]*listen[\s]*=' -r 'listen = [::]:9000' \
        --path=/opt/docker/etc/php/fpm/ \
        --path-pattern='*.conf'

fi

if [[ "$(version-compare "$PHP_VERSION" "5.99.999")" == "<" ]]; then
    # Configure php-fpm main (php 5.x)
    go-replace --mode=lineinfile --regex \
        --lineinfile-after='\[global\]' \
        -s '^[\s;]*daemonize[\s]*=' -r 'daemonize = no' \
        -- /opt/docker/etc/php/fpm/php-fpm.conf
fi
