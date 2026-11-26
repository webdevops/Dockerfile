# If /dev/log is either a named pipe or it was placed there accidentally,
# e.g. because of the issue documented at https://github.com/phusion/baseimage-docker/pull/25,
# then we remove it.
if [ ! -S /dev/log ]; then rm -f /dev/log; fi
if [ ! -S /var/lib/syslog-ng/syslog-ng.ctl ]; then rm -f /var/lib/syslog-ng/syslog-ng.ctl; fi

# The configuration as the image has it, at every start of syslog-ng: SERVICE_SYSLOG_LOG and SERVICE_SYSLOG_ERROR_LOG
# below replace its targets
container-file-auto-restore /opt/docker/etc/syslog-ng/syslog-ng.conf

# SERVICE_SYSLOG_LOG and SERVICE_SYSLOG_ERROR_LOG: the messages up to the level warning and those from the level err go
# to these files instead of /docker.stdout and /docker.stderr, for example /dev/null to drop them
SYSLOG_LOG="${SERVICE_SYSLOG_LOG:-/docker.stdout}"
SYSLOG_ERROR_LOG="${SERVICE_SYSLOG_ERROR_LOG:-/docker.stderr}"
go-replace \
    -s 'pipe("/docker.stdout"' -r "pipe(\"${SYSLOG_LOG}\"" \
    -s 'pipe("/docker.stderr"' -r "pipe(\"${SYSLOG_ERROR_LOG}\"" \
    -- /opt/docker/etc/syslog-ng/syslog-ng.conf

# /docker.stdout and /docker.stderr are pipes of the container runtime, or files, for example of LOG_STDOUT and
# LOG_STDERR, or in a container with a tty; the targets of the variables are files, or /dev/null
for LOG_TARGET in "$SYSLOG_LOG" "$SYSLOG_ERROR_LOG"; do
    if [[ ! -p "$LOG_TARGET" ]]; then
        go-replace -s "pipe(\"$LOG_TARGET\"" -r "file(\"$LOG_TARGET\"" -- /opt/docker/etc/syslog-ng/syslog-ng.conf
    fi
done
