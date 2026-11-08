#!/usr/bin/env bash

# Link main php-fpm binary
ln -sf -- "$PHP_FPM_BIN" /usr/local/bin/php-fpm

# Move php-fpm main file to /opt/docker/etc/php/fpm/ and create symlink
if [[ ! -f /opt/docker/etc/php/fpm/php-fpm.conf ]]; then
       mv -- "$PHP_MAIN_CONF" /opt/docker/etc/php/fpm/php-fpm.conf
else
       rm -f -- "$PHP_MAIN_CONF"
fi
ln -sf -- /opt/docker/etc/php/fpm/php-fpm.conf "$PHP_MAIN_CONF"

# Configure php-fpm main (all versions)
go-replace --mode=lineinfile --regex \
    --lineinfile-after='\[global\]' \
    -s '^[\s;]*error_log[\s]*=' -r 'error_log = /docker.stderr' \
    -s '^[\s;]*pid[\s]*='       -r 'pid = /var/run/php-fpm.pid' \
    -- /opt/docker/etc/php/fpm/php-fpm.conf

# Listen on all IPv6 and IPv4 addresses. Without IPv6 in the container,
# entrypoint.d/20-php-fpm.sh switches to 0.0.0.0:9000 at start.
go-replace --mode=line --regex \
    -s '^[\s;]*listen[\s]*=' -r 'listen = [::]:9000' \
    --path=/opt/docker/etc/php/fpm/ \
    --path-pattern='*.conf'
