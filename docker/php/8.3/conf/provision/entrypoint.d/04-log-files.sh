# LOG_STDOUT and LOG_STDERR: creates a file that does not exist yet as root:adm 0640 before the services start, so that
# it gets the same owner and mode whichever service opens it first; syslog-ng no longer sets them (owner(), group() and
# perm() of its destination are empty). A file that exists keeps its owner and mode. Without its directory, nothing is
# created; the services then fail to open it, as before.
for LOG_FILE in "${LOG_STDOUT:-}" "${LOG_STDERR:-}"; do
    if [[ -n "$LOG_FILE" && ! -e "$LOG_FILE" && -d "$(dirname "$LOG_FILE")" ]]; then
        install -m 0640 -o root -g adm /dev/null "$LOG_FILE"
    fi
done
