#!/usr/bin/env bash

# Init vars
if [[ -z "$SERVICE_SSH_OPTS" ]]; then SERVICE_SSH_OPTS=""; fi

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/ssh.d/"

exec /usr/sbin/sshd -D $SERVICE_SSH_OPTS
