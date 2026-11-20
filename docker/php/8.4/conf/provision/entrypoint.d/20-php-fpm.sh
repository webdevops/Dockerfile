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

# The values of the variables go to a pool file of their own, written anew at every start. PHP-FPM reads it after
# application.conf and after docker.conf of the official PHP image, so its directives win over theirs, also over
# listen, access.log and clear_env of docker.conf. application.conf stays as the image or you have it.
FPM_POOL_ENVIRONMENT_CONF=/opt/docker/etc/php/fpm/pool.d/zz-environment.conf
echo '; container env settings, written at every start' > "$FPM_POOL_ENVIRONMENT_CONF"
echo '[www]' >> "$FPM_POOL_ENVIRONMENT_CONF"

# General fpm pool setting
for ENV_VAR in $(envListVars "fpm\.pool\."); do
    env_key=${ENV_VAR#fpm.pool.}
    env_val=$(envGetValue "$ENV_VAR")

    echo "$env_key = ${env_val}" >> "$FPM_POOL_ENVIRONMENT_CONF"
done


if [[ -n "${FPM_PM_MAX_CHILDREN+x}" ]]; then
    echo "pm.max_children = ${FPM_PM_MAX_CHILDREN}" >> "$FPM_POOL_ENVIRONMENT_CONF"
fi

if [[ -n "${FPM_PM_START_SERVERS+x}" ]]; then
    echo "pm.start_servers = ${FPM_PM_START_SERVERS}" >> "$FPM_POOL_ENVIRONMENT_CONF"
fi

if [[ -n "${FPM_PM_MIN_SPARE_SERVERS+x}" ]]; then
    echo "pm.min_spare_servers = ${FPM_PM_MIN_SPARE_SERVERS}" >> "$FPM_POOL_ENVIRONMENT_CONF"
fi

if [[ -n "${FPM_PM_MAX_SPARE_SERVERS+x}" ]]; then
    echo "pm.max_spare_servers = ${FPM_PM_MAX_SPARE_SERVERS}" >> "$FPM_POOL_ENVIRONMENT_CONF"
fi

if [[ -n "${FPM_PROCESS_IDLE_TIMEOUT+x}" ]]; then
    echo "pm.process_idle_timeout = ${FPM_PROCESS_IDLE_TIMEOUT}" >> "$FPM_POOL_ENVIRONMENT_CONF"
fi

if [[ -n "${FPM_MAX_REQUESTS+x}" ]]; then
    echo "pm.max_requests = ${FPM_MAX_REQUESTS}" >> "$FPM_POOL_ENVIRONMENT_CONF"
fi

if [[ -n "${FPM_REQUEST_TERMINATE_TIMEOUT+x}" ]]; then
    echo "request_terminate_timeout = ${FPM_REQUEST_TERMINATE_TIMEOUT}" >> "$FPM_POOL_ENVIRONMENT_CONF"
fi

if [[ -n "${FPM_RLIMIT_FILES+x}" ]]; then
    echo "rlimit_files = ${FPM_RLIMIT_FILES}" >> "$FPM_POOL_ENVIRONMENT_CONF"
fi

if [[ -n "${FPM_RLIMIT_CORE+x}" ]]; then
    echo "rlimit_core = ${FPM_RLIMIT_CORE}" >> "$FPM_POOL_ENVIRONMENT_CONF"
fi

# The pool logs the errors of PHP scripts to /docker.stderr with php_admin_value, which a php.ini value cannot override,
# so php.error_log replaces it here. PHP-FPM takes the first of two php_admin_value lines, so a line in
# zz-environment.conf would not take effect.
if [[ -n "$(envListVars 'php\.error_log=')" ]]; then
    go-replace --mode=lineinfile --regex \
        -s '^[\s;]*php_admin_value\[error_log\][\s]*=' -r "php_admin_value[error_log] = $(envGetValue php.error_log)" \
        -- /opt/docker/etc/php/fpm/pool.d/application.conf
fi

# Workaround for official PHP images
if [[ -n "${PHP_SENDMAIL_PATH+x}" ]]; then
    echo "php_admin_value[sendmail_path] = ${PHP_SENDMAIL_PATH}" >> "$FPM_POOL_ENVIRONMENT_CONF"
fi
