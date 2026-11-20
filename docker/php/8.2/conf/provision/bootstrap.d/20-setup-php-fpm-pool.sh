#!/usr/bin/env bash

# Rename pool file file to application.conf
if [[ ! -f "/opt/docker/etc/php/fpm/pool.d/application.conf" ]]; then
    # Move php-fpm pool directory file to /opt/docker/etc/php/
    mv -- "$PHP_POOL_DIR"  /opt/docker/etc/php/fpm/pool.d

    mv -- "/opt/docker/etc/php/fpm/pool.d/${PHP_POOL_CONF}" /opt/docker/etc/php/fpm/pool.d/application.conf
fi

# Remove php-fpm pool directory
rm -rf -- "$PHP_POOL_DIR"

# Symlink php-fpm pool file to original destination
ln -sf -- /opt/docker/etc/php/fpm/pool.d "$PHP_POOL_DIR"

# Configure php-fpm pool (application.conf)
go-replace --mode=lineinfile --regex \
    -s '^[\s;]*catch_workers_output[\s]*='          -r 'catch_workers_output = yes' \
    -s '^[\s;]*access.format[\s]*='                 -r 'access.format = "[php-fpm:access] %R - %u %t \"%m %r%Q%q\" %s %f %{mili}d %{kilo}M %C%%"' \
    -s '^[\s;]*access.log[\s]*='                    -r 'access.log = /docker.stdout' \
    -s '^[\s;]*slowlog[\s]*='                       -r 'slowlog = /docker.stderr' \
    -s '^[\s;]*php_admin_value\[error_log\][\s]*='  -r 'php_admin_value[error_log] = /docker.stderr' \
    -s '^[\s;]*php_admin_value\[log_errors\][\s]*=' -r 'php_admin_value[log_errors] = on' \
    -s '^[\s;]*listen.allowed_clients[\s]*='        -r ";listen.allowed_clients" \
    -- /opt/docker/etc/php/fpm/pool.d/application.conf

# docker.conf of the official image comes after php-fpm.conf and application.conf and sends the error log and the
# access log of PHP-FPM to its own stderr: to /docker.stderr and /docker.stdout instead, like the other services, so
# that LOG_STDERR and LOG_STDOUT apply. The master process opens both as root, so the errors of PHP scripts that a
# worker may not write to /docker.stderr itself reach that file through the master too.
if [[ -f /opt/docker/etc/php/fpm/pool.d/docker.conf ]]; then
    go-replace --mode=line --regex \
        -s '^[\s]*error_log[\s]*='  -r 'error_log = /docker.stderr' \
        -s '^[\s]*access.log[\s]*=' -r 'access.log = /docker.stdout' \
        -- /opt/docker/etc/php/fpm/pool.d/docker.conf
fi

# Fix user setting
go-replace --mode=line --regex \
    -s '^[\s;]*user[\s]*='  -r "user = $APPLICATION_USER" \
    -s '^[\s;]*group[\s]*=' -r "group = $APPLICATION_GROUP" \
    --path=/opt/docker/etc/php/fpm/ \
    --path-pattern='*.conf'

# Keep the environment variables of the container for PHP
go-replace --mode=lineinfile --regex \
    -s '^[\s;]*clear_env[\s]*='                        -r 'clear_env = no' \
    -- /opt/docker/etc/php/fpm/pool.d/application.conf
