#!/usr/bin/env bash

# Init vars
if [[ -z "$SERVICE_SSH_OPTS" ]]; then SERVICE_SSH_OPTS=""; fi

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/ssh.d/"

# -e: sshd logs to its stderr, which supervisord writes to the container log, instead of to syslog, which does not run in
# every image and where syslog-ng drops the facility auth of sshd
exec /usr/sbin/sshd -D -e $SERVICE_SSH_OPTS
