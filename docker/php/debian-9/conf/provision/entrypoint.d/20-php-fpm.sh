
#######################################
### FPM MAIN
#######################################

if [[ -n "${FPM_PROCESS_MAX+x}" ]]; then
    go-replace --mode=lineinfile --regex \
        --lineinfile-after='\[global\]' \
        -s '^[\s;]*process.max[\s]*=' -r "process.max = ${CONF_PHP_FPM_MAIN}"
        -- /opt/docker/etc/php/fpm/php-fpm.conf
fi

#######################################
### FPM POOL
#######################################

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
