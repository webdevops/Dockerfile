# LOG_STDOUT and LOG_STDERR: creates a file that does not exist yet as root:root 0644 before the services start, so that
# it gets the same owner and mode whichever service opens it first; syslog-ng no longer sets them (owner(), group() and
# perm() of its destination are empty). A file that exists keeps its owner and mode. If the file cannot be created or
# written, for example in a missing or read-only directory, /docker.stdout or /docker.stderr points to the container log
# again, where its logs stay: the services, which supervisord starts with their output in /docker.stdout and
# /docker.stderr, would not start otherwise.
for LOG_VARIABLE in LOG_STDOUT LOG_STDERR; do
    LOG_FILE="${!LOG_VARIABLE:-}"
    if [[ -z "$LOG_FILE" ]]; then
        continue
    fi

    if [[ ! -e "$LOG_FILE" ]]; then
        install -m 0644 -o root -g root /dev/null "$LOG_FILE" 2> /dev/null || true
    fi

    if [[ ! -w "$LOG_FILE" ]]; then
        echo "Cannot write to $LOG_FILE of $LOG_VARIABLE: its logs stay in the container log" >&2
        if [[ "$LOG_VARIABLE" == LOG_STDOUT ]]; then
            ln -f -s "/proc/$$/fd/1" /docker.stdout
        else
            ln -f -s "/proc/$$/fd/2" /docker.stderr
        fi
    fi
done
