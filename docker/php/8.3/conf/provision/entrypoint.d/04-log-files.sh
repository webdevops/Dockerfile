# LOG_STDOUT, LOG_STDERR and the log variables of the services (SERVICE_*_LOG): creates a file that does not exist yet
# as root:root 0644 before the services start, so that it gets the same owner and mode whichever service opens it
# first; syslog-ng no longer sets them (owner(), group() and perm() of its destination are empty). A file that exists,
# for example /dev/null or /docker.stdout, keeps its owner and mode. Without its directory, nothing is created. If the
# file cannot be created, for example in a read-only directory, the container starts all the same and the services that
# write there fail to open it, as before.
for LOG_VARIABLE in LOG_STDOUT LOG_STDERR \
    SERVICE_NGINX_ACCESS_LOG SERVICE_NGINX_ERROR_LOG \
    SERVICE_APACHE_ACCESS_LOG SERVICE_APACHE_ERROR_LOG \
    SERVICE_PHPFPM_ACCESS_LOG SERVICE_PHPFPM_ERROR_LOG \
    SERVICE_SYSLOG_LOG SERVICE_SYSLOG_ERROR_LOG; do
    LOG_FILE="${!LOG_VARIABLE:-}"
    if [[ -n "$LOG_FILE" && ! -e "$LOG_FILE" && -d "$(dirname "$LOG_FILE")" ]]; then
        install -m 0644 -o root -g root /dev/null "$LOG_FILE" \
            || echo "Cannot create $LOG_FILE for $LOG_VARIABLE" >&2
    fi
done
