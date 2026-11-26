# If /dev/log is either a named pipe or it was placed there accidentally,
# e.g. because of the issue documented at https://github.com/phusion/baseimage-docker/pull/25,
# then we remove it.
if [ ! -S /dev/log ]; then rm -f /dev/log; fi
if [ ! -S /var/lib/syslog-ng/syslog-ng.ctl ]; then rm -f /var/lib/syslog-ng/syslog-ng.ctl; fi

# /docker.stdout and /docker.stderr are pipes of the container runtime, or files, for example of LOG_STDOUT and
# LOG_STDERR, or in a container with a tty
for LOG_TARGET in /docker.stdout /docker.stderr; do
    if [[ ! -p "$LOG_TARGET" ]]; then
        go-replace -s "pipe(\"$LOG_TARGET\"" -r "file(\"$LOG_TARGET\"" -- /opt/docker/etc/syslog-ng/syslog-ng.conf
    fi
done
