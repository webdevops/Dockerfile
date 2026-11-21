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

# Whether a pool file sets the process manager to other values than application.conf of the image, which has those of
# www.conf of the official PHP image: e.g. an application.conf or a www.conf of your own. zz-environment.conf does not
# count, it is being written.
fpmPoolFilesSetTheProcessManager() {
    local file
    local files=(/dev/null)
    for file in /opt/docker/etc/php/fpm/pool.d/*.conf; do
        if [[ "$file" != "$FPM_POOL_ENVIRONMENT_CONF" ]]; then
            files+=("$file")
        fi
    done

    awk '
        BEGIN {
            split("pm=dynamic pm.max_children=5 pm.start_servers=2 pm.min_spare_servers=1 pm.max_spare_servers=3", values, " ")
            for (i in values) image[values[i]] = 1
        }
        { sub(/;.*/, ""); gsub(/[ \t\r"]/, "") }
        /^pm(\.max_children|\.start_servers|\.min_spare_servers|\.max_spare_servers)?=/ && !($0 in image) { other = 1 }
        END { exit !other }
    ' "${files[@]}"
}

# The value set for pm.<$1>: FPM_PM_<$2>, which is written after fpm.pool.pm.<$1> and wins, else fpm.pool.pm.<$1>.
fpmPoolSetValue() {
    local variable="FPM_PM_$2"
    if [[ -n "${!variable+x}" ]]; then
        echo "${!variable}"
    else
        envGetValue "fpm.pool.pm.$1"
    fi
}

# Sizes the process manager to the CPUs and the memory of the container, see docs/environment-variables.md#php-fpm:
# pm.max_children is the smaller of the workers of FPM_AUTO_CHILD_MEMORY that fit into the memory beside OPcache and
# 256 MB for the other processes, and of FPM_AUTO_CHILDREN_PER_CPU per CPU, at least 2; the spare servers follow the
# CPUs. A value set with FPM_PM_* or fpm.pool.pm.* stays, and the others are computed to fit it.
fpmPoolSize() {
    local cpus memory quota="" period="" limit="" cpuSource="no limit" memorySource="no limit"
    local childMemory perCpu opcacheMemory value
    local setChildren setStart setMin setMax children start min max
    local positive='^[1-9][0-9]*$'

    # the CPU limit of the container, rounded up, at most the CPUs it may run on
    cpus=$(nproc)
    if [[ -r /sys/fs/cgroup/cpu.max ]]; then
        read -r quota period < /sys/fs/cgroup/cpu.max || true
        limit="cgroup v2 limit"
    elif [[ -r /sys/fs/cgroup/cpu/cpu.cfs_quota_us && -r /sys/fs/cgroup/cpu/cpu.cfs_period_us ]]; then
        quota=$(cat /sys/fs/cgroup/cpu/cpu.cfs_quota_us)
        period=$(cat /sys/fs/cgroup/cpu/cpu.cfs_period_us)
        limit="cgroup v1 limit"
    fi
    if [[ "$quota" =~ $positive && "$period" =~ $positive ]] && (( (quota + period - 1) / period < cpus )); then
        cpus=$(( (quota + period - 1) / period ))
        cpuSource=$limit
    fi

    # the memory limit of the container; without one, or a larger one, the memory of the host
    memory=$(awk '/^MemTotal:/ { print int($2 / 1024) }' /proc/meminfo)
    limit=""
    if [[ -r /sys/fs/cgroup/memory.max ]]; then
        limit=$(cat /sys/fs/cgroup/memory.max)
        memorySource="cgroup v2 limit"
    elif [[ -r /sys/fs/cgroup/memory/memory.limit_in_bytes ]]; then
        limit=$(cat /sys/fs/cgroup/memory/memory.limit_in_bytes)
        memorySource="cgroup v1 limit"
    fi
    if [[ "$limit" =~ $positive ]] && (( limit / 1048576 < memory )); then
        memory=$(( limit / 1048576 ))
    else
        memorySource="no limit"
    fi

    if [[ "${FPM_AUTO_CHILD_MEMORY:-128M}" =~ ^([1-9][0-9]*)([MmGg]?)$ ]]; then
        childMemory=${BASH_REMATCH[1]}
        if [[ "${BASH_REMATCH[2]}" == [Gg] ]]; then
            childMemory=$(( childMemory * 1024 ))
        fi
    else
        echo "PHP-FPM: FPM_AUTO_CHILD_MEMORY=${FPM_AUTO_CHILD_MEMORY} is not a size such as 128M, using 128M" >&2
        childMemory=128
    fi

    perCpu=${FPM_AUTO_CHILDREN_PER_CPU:-4}
    if [[ ! "$perCpu" =~ $positive ]]; then
        echo "PHP-FPM: FPM_AUTO_CHILDREN_PER_CPU=${perCpu} is not a positive number, using 4" >&2
        perCpu=4
    fi

    # PHP_OPCACHE_MEMORY_CONSUMPTION is written after php.opcache.memory_consumption and wins
    opcacheMemory=${PHP_OPCACHE_MEMORY_CONSUMPTION-$(envGetValue php.opcache.memory_consumption)}
    if [[ ! "$opcacheMemory" =~ $positive ]]; then
        opcacheMemory=256
    fi

    setChildren=$(fpmPoolSetValue max_children MAX_CHILDREN)
    setStart=$(fpmPoolSetValue start_servers START_SERVERS)
    setMin=$(fpmPoolSetValue min_spare_servers MIN_SPARE_SERVERS)
    setMax=$(fpmPoolSetValue max_spare_servers MAX_SPARE_SERVERS)

    children=$(( (memory - opcacheMemory - 256) / childMemory ))
    if (( cpus * perCpu < children )); then
        children=$(( cpus * perCpu ))
    fi
    for value in 2 "$setStart" "$setMin" "$setMax"; do
        if [[ "$value" =~ $positive ]] && (( value > children )); then
            children=$value
        fi
    done
    if [[ "$setChildren" =~ $positive ]]; then
        children=$setChildren
    fi

    min=$(( cpus / 2 ))
    for value in "$children" "$setMax" "$setStart"; do
        if [[ "$value" =~ $positive ]] && (( value < min )); then
            min=$value
        fi
    done
    if (( min < 1 )); then
        min=1
    fi
    if [[ "$setMin" =~ $positive ]]; then
        min=$setMin
    fi

    max=$(( cpus * 2 ))
    if (( children < max )); then
        max=$children
    fi
    for value in "$min" "$setStart"; do
        if [[ "$value" =~ $positive ]] && (( value > max )); then
            max=$value
        fi
    done
    if [[ "$setMax" =~ $positive ]]; then
        max=$setMax
    fi

    # the default of PHP-FPM
    start=$(( min + (max - min) / 2 ))
    if [[ "$setStart" =~ $positive ]]; then
        start=$setStart
    fi

    # only the values that are not set; zz-environment.conf gets the ones that are set below
    if [[ -z "$setChildren" ]]; then echo "pm.max_children = ${children}" >> "$FPM_POOL_ENVIRONMENT_CONF"; fi
    if [[ -z "$setStart" ]]; then echo "pm.start_servers = ${start}" >> "$FPM_POOL_ENVIRONMENT_CONF"; fi
    if [[ -z "$setMin" ]]; then echo "pm.min_spare_servers = ${min}" >> "$FPM_POOL_ENVIRONMENT_CONF"; fi
    if [[ -z "$setMax" ]]; then echo "pm.max_spare_servers = ${max}" >> "$FPM_POOL_ENVIRONMENT_CONF"; fi

    echo "PHP-FPM: ${cpus} CPUs (${cpuSource}), ${memory} MB memory (${memorySource}) ->" \
        "pm.max_children=${children}${setChildren:+ (set)}, pm.start_servers=${start}${setStart:+ (set)}," \
        "pm.min_spare_servers=${min}${setMin:+ (set)}, pm.max_spare_servers=${max}${setMax:+ (set)}"
}

if [[ "${FPM_AUTO_SIZE:-1}" != "0" ]]; then
    if fpmPoolFilesSetTheProcessManager; then
        echo "PHP-FPM: a pool file sets pm.* to other values than the image, so the pool is not sized to the container"
    else
        fpmPoolSize
    fi
fi
unset -f fpmPoolFilesSetTheProcessManager fpmPoolSetValue fpmPoolSize

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
