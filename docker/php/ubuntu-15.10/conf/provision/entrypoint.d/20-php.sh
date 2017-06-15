container-file-auto-restore "/opt/docker/etc/php/php.webdevops.ini"

echo '' >> /opt/docker/etc/php/php.webdevops.ini
echo '; container env settings' >> /opt/docker/etc/php/php.webdevops.ini

# General php setting
for ENV_VAR in $(envListVars "php\."); do
    env_key=${ENV_VAR#php.}
    env_val=$(envGetValue "$ENV_VAR")

    echo "$env_key = ${env_val}" >> /opt/docker/etc/php/php.webdevops.ini
done


if [[ -n "${PHP_DATE_TIMEZONE+x}" ]]; then
    echo "date.timezone = ${PHP_DATE_TIMEZONE}" >> /opt/docker/etc/php/php.webdevops.ini
fi

if [[ -n "${PHP_DISPLAY_ERRORS+x}" ]]; then
    echo "display_errors = ${PHP_DISPLAY_ERRORS}" >> /opt/docker/etc/php/php.webdevops.ini
fi

if [[ -n "${PHP_MEMORY_LIMIT+x}" ]]; then
    echo "memory_limit = ${PHP_MEMORY_LIMIT}" >> /opt/docker/etc/php/php.webdevops.ini
fi

if [[ -n "${PHP_MAX_EXECUTION_TIME+x}" ]]; then
    echo "max_execution_time = ${PHP_MAX_EXECUTION_TIME}" >> /opt/docker/etc/php/php.webdevops.ini
fi

if [[ -n "${PHP_POST_MAX_SIZE+x}" ]]; then
    echo "post_max_size = ${PHP_POST_MAX_SIZE}" >> /opt/docker/etc/php/php.webdevops.ini
fi

if [[ -n "${PHP_UPLOAD_MAX_FILESIZE+x}" ]]; then
    echo "upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE}" >> /opt/docker/etc/php/php.webdevops.ini
fi

if [[ -n "${PHP_OPCACHE_MEMORY_CONSUMPTION+x}" ]]; then
    echo "opcache.memory_consumption = ${PHP_OPCACHE_MEMORY_CONSUMPTION}" >> /opt/docker/etc/php/php.webdevops.ini
fi

if [[ -n "${PHP_OPCACHE_MAX_ACCELERATED_FILES+x}" ]]; then
    echo "opcache.max_accelerated_files = ${PHP_OPCACHE_MAX_ACCELERATED_FILES}" >> /opt/docker/etc/php/php.webdevops.ini
fi

if [[ -n "${PHP_OPCACHE_VALIDATE_TIMESTAMPS+x}" ]]; then
    echo "opcache.validate_timestamps = ${PHP_OPCACHE_VALIDATE_TIMESTAMPS}" >> /opt/docker/etc/php/php.webdevops.ini
fi

if [[ -n "${PHP_OPCACHE_REVALIDATE_FREQ+x}" ]]; then
    echo "opcache.revalidate_freq = ${PHP_OPCACHE_REVALIDATE_FREQ}" >> /opt/docker/etc/php/php.webdevops.ini
fi

if [[ -n "${PHP_OPCACHE_INTERNED_STRINGS_BUFFER+x}" ]]; then
    echo "opcache.interned_strings_buffer = ${PHP_OPCACHE_INTERNED_STRINGS_BUFFER}" >> /opt/docker/etc/php/php.webdevops.ini
fi


