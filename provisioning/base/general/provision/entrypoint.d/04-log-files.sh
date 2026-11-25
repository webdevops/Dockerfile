# LOG_STDOUT and LOG_STDERR: creates a file that does not exist yet as root:root 0644 before the services start, so that
# it gets the same owner and mode whichever service opens it first; syslog-ng no longer sets them (owner(), group() and
# perm() of its destination are empty). A file that exists keeps its owner and mode. Without its directory, nothing is
# created. If the file cannot be created, for example in a read-only directory, the container starts all the same and
# the services that write there fail to open it, as before.
for LOG_FILE in "${LOG_STDOUT:-}" "${LOG_STDERR:-}"; do
    if [[ -n "$LOG_FILE" && ! -e "$LOG_FILE" && -d "$(dirname "$LOG_FILE")" ]]; then
        install -m 0644 -o root -g root /dev/null "$LOG_FILE" \
            || echo "Cannot create $LOG_FILE for LOG_STDOUT or LOG_STDERR" >&2
    fi
done
