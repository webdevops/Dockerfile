# If /dev/log is either a named pipe or it was placed there accidentally,
# e.g. because of the issue documented at https://github.com/phusion/baseimage-docker/pull/25,
# then we remove it.
if [ ! -S /dev/log ]; then rm -f /dev/log; fi
if [ ! -S /var/lib/syslog-ng/syslog-ng.ctl ]; then rm -f /var/lib/syslog-ng/syslog-ng.ctl; fi

if [[ ! -p /docker.stdout ]]; then
    # Switch to file (tty docker mode)
    rpl --quiet 'pipe("/docker.stdout")' 'file("/docker.stdout")' -- /opt/docker/etc/syslog-ng/syslog-ng.conf &> /dev/null
fi
