# LOG_STDOUT, LOG_STDERR and the log variables of the services (SERVICE_*_LOG): creates a file that does not exist yet
# as root:root 0644 before the services start, so that it gets the same owner and mode whichever service opens it
# first; syslog-ng no longer sets them (owner(), group() and perm() of its destination are empty). A file that exists,
# for example /dev/null, keeps its owner and mode. If the file cannot be created or written, for example in a missing or
# read-only directory, its logs stay in the container log: /docker.stdout or /docker.stderr points to it again, or the
# variable of the service is dropped. The services, which supervisord starts with their output in /docker.stdout and
# /docker.stderr, would not start otherwise, nor would a web server whose log it cannot open.
for LOG_VARIABLE in LOG_STDOUT LOG_STDERR \
    SERVICE_NGINX_ACCESS_LOG SERVICE_NGINX_ERROR_LOG \
    SERVICE_APACHE_ACCESS_LOG SERVICE_APACHE_ERROR_LOG \
    SERVICE_PHPFPM_ACCESS_LOG SERVICE_PHPFPM_ERROR_LOG \
    SERVICE_SYSLOG_LOG SERVICE_SYSLOG_ERROR_LOG; do
    LOG_FILE="${!LOG_VARIABLE:-}"
    if [[ -z "$LOG_FILE" ]]; then
        continue
    fi

    if [[ ! -e "$LOG_FILE" ]]; then
        install -m 0644 -o root -g root /dev/null "$LOG_FILE" 2> /dev/null || true
    fi

    if [[ ! -w "$LOG_FILE" ]]; then
        echo "Cannot write to $LOG_FILE of $LOG_VARIABLE: its logs stay in the container log" >&2
        case "$LOG_VARIABLE" in
            LOG_STDOUT) ln -f -s "/proc/$$/fd/1" /docker.stdout ;;
            LOG_STDERR) ln -f -s "/proc/$$/fd/2" /docker.stderr ;;
            *) unset "$LOG_VARIABLE" ;;
        esac
    fi
done
