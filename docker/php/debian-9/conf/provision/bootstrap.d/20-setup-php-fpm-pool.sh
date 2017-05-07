#!/usr/bin/env bash

# Move php-fpm pool directory file to /opt/docker/etc/php/
mv -- "$PHP_POOL_DIR"  /opt/docker/etc/php/fpm/pool.d

# Rename pool file file to application.conf
mv -- "/opt/docker/etc/php/fpm/pool.d/${PHP_POOL_CONF}" /opt/docker/etc/php/fpm/pool.d/application.conf

# Remove php-fpm pool directory
rm -rf -- "$PHP_POOL_DIR"

# Symlink php-fpm pool file to original destination
ln -sf -- /opt/docker/etc/php/fpm/pool.d "$PHP_POOL_DIR"

# Configure php-fpm pool user (application.conf)
go-replace --mode=lineinfile --regex \
    -s '^[\s;]*listen[\s]*='                        -r 'listen = [::]:9000' \
    -s '^[\s;]*catch_workers_output[\s]*='          -r 'catch_workers_output = yes' \
    -s '^[\s;]*access.format[\s]*='                 -r 'access.format = "[php-fpm:access] %R - %u %t \"%m %r%Q%q\" %s %f %{mili}d %{kilo}M %C%%"' \
    -s '^[\s;]*access.log[\s]*='                    -r 'access.log = /docker.stdout' \
    -s '^[\s;]*slowlog[\s]*='                       -r 'slowlog = /docker.stderr' \
    -s '^[\s;]*php_admin_value\[error_log\][\s]*='  -r 'php_admin_value[error_log] = /docker.stderr' \
    -s '^[\s;]*php_admin_value\[log_errors\][\s]*=' -r 'php_admin_value[log_errors] = on' \
    -s '^[\s;]*user[\s]*='                          -r "user = $APPLICATION_USER" \
    -s '^[\s;]*group[\s]*='                         -r "user = $APPLICATION_GROUP" \
    -s '^[\s;]*listen.allowed_clients[\s]*='        -r ";listen.allowed_clients" \
    -- /opt/docker/etc/php/fpm/pool.d/application.conf

if [[ "$(versionCompare "$PHP_VERSION" "5.5.999")" == "<" ]]; then
    # no ipv6 sockets available for old php version
    go-replace --mode=lineinfile --regex \
        -s '^[\s;]*listen[\s]*='                        -r 'listen = 0.0.0.0:9000' \
        -- /opt/docker/etc/php/fpm/pool.d/application.conf
fi

if [[ "$PHP_CLEAR_ENV_AVAILABLE" -eq 1 ]]; then
    # Clear env setting available, disable clearing of environment variables
    go-replace --mode=lineinfile --regex \
        -s '^[\s;]*clear_env[\s]*='                        -r 'clear_env = no' \
        -- /opt/docker/etc/php/fpm/pool.d/application.conf
    rm -f /opt/docker/bin/service.d/php-fpm.d/11-clear-env.sh
else
    # Append clear env workaround in php-fpm pool (old php-fpm versions)
    echo ';#CLEAR_ENV_WORKAROUND#' >> /opt/docker/etc/php/fpm/pool.d/application.conf

fi
