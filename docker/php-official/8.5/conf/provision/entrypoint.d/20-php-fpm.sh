#######################################
### FPM MAIN
#######################################

container-file-auto-restore "/opt/docker/etc/php/fpm/php-fpm.conf"

echo '' >> /opt/docker/etc/php/fpm/php-fpm.conf
echo '; container env settings' >> /opt/docker/etc/php/fpm/php-fpm.conf
echo '[global]' >> /opt/docker/etc/php/fpm/php-fpm.conf

if [[ -n "${FPM_PROCESS_MAX+x}" ]]; then
    echo "process.max = ${FPM_PROCESS_MAX}" >> /opt/docker/etc/php/fpm/php-fpm.conf
fi

# General fpm main setting
for ENV_VAR in $(envListVars "fpm\.global\."); do
    env_key=${ENV_VAR#fpm.global.}
    env_val=$(envGetValue "$ENV_VAR")

    echo "$env_key = ${env_val}" >> /opt/docker/etc/php/fpm/php-fpm.conf
done

#######################################
### FPM POOL
#######################################

container-file-auto-restore "/opt/docker/etc/php/fpm/pool.d/application.conf"

echo '' >> /opt/docker/etc/php/fpm/pool.d/application.conf
echo '; container env settings' >> /opt/docker/etc/php/fpm/pool.d/application.conf

# General fpm pool setting
for ENV_VAR in $(envListVars "fpm\.pool\."); do
    env_key=${ENV_VAR#fpm.pool.}
    env_val=$(envGetValue "$ENV_VAR")

    echo "$env_key = ${env_val}" >> /opt/docker/etc/php/fpm/pool.d/application.conf
done


if [[ -n "${FPM_PM_MAX_CHILDREN+x}" ]]; then
    echo "pm.max_children = ${FPM_PM_MAX_CHILDREN}" >> /opt/docker/etc/php/fpm/pool.d/application.conf
fi

if [[ -n "${FPM_PM_START_SERVERS+x}" ]]; then
    echo "pm.start_servers = ${FPM_PM_START_SERVERS}" >> /opt/docker/etc/php/fpm/pool.d/application.conf
fi

if [[ -n "${FPM_PM_MIN_SPARE_SERVERS+x}" ]]; then
    echo "pm.min_spare_servers = ${FPM_PM_MIN_SPARE_SERVERS}" >> /opt/docker/etc/php/fpm/pool.d/application.conf
fi

if [[ -n "${FPM_PM_MAX_SPARE_SERVERS+x}" ]]; then
    echo "pm.max_spare_servers = ${FPM_PM_MAX_SPARE_SERVERS}" >> /opt/docker/etc/php/fpm/pool.d/application.conf
fi

if [[ -n "${FPM_PROCESS_IDLE_TIMEOUT+x}" ]]; then
    echo "pm.process_idle_timeout = ${FPM_PROCESS_IDLE_TIMEOUT}" >> /opt/docker/etc/php/fpm/pool.d/application.conf
fi

if [[ -n "${FPM_MAX_REQUESTS+x}" ]]; then
    echo "pm.max_requests = ${FPM_MAX_REQUESTS}" >> /opt/docker/etc/php/fpm/pool.d/application.conf
fi

if [[ -n "${FPM_REQUEST_TERMINATE_TIMEOUT+x}" ]]; then
    echo "request_terminate_timeout = ${FPM_REQUEST_TERMINATE_TIMEOUT}" >> /opt/docker/etc/php/fpm/pool.d/application.conf
fi

if [[ -n "${FPM_RLIMIT_FILES+x}" ]]; then
    echo "rlimit_files = ${FPM_RLIMIT_FILES}" >> /opt/docker/etc/php/fpm/pool.d/application.conf
fi

if [[ -n "${FPM_RLIMIT_CORE+x}" ]]; then
    echo "rlimit_core = ${FPM_RLIMIT_CORE}" >> /opt/docker/etc/php/fpm/pool.d/application.conf
fi

# Workaround for official PHP images
if [[ -n "${PHP_SENDMAIL_PATH+x}" ]]; then
    echo "php_admin_value[sendmail_path] = ${PHP_SENDMAIL_PATH}" >> /opt/docker/etc/php/fpm/pool.d/application.conf
fi

# Without IPv6 in the container PHP-FPM cannot listen on [::], so it listens on all IPv4 addresses
if [[ ! -e /proc/net/if_inet6 ]]; then
    for FILE in $(find /opt/docker/etc/php/fpm/ -type f -name '*.conf'); do
        if grep -qE '^[[:space:]]*listen[[:space:]]*=[[:space:]]*\[::\]:' "$FILE"; then
            echo "IPv6 is not available, PHP-FPM listens on 0.0.0.0 instead of [::] in $FILE"
            if ! sed -i -E '/^[[:space:]]*listen[[:space:]]*=/ s/\[::\]:/0.0.0.0:/' "$FILE"; then
                echo "WARNING: Cannot change $FILE; PHP-FPM will not start without IPv6"
            fi
        fi
    done
fi
