#!/usr/bin/env bash
# postfix-wrapper.sh, version 0.1.0
#
# You cannot start postfix in some foreground mode and
# it's more or less important that docker doesn't kill
# postfix and its chilren if you stop the container.
#
# Use this script with supervisord and it will take
# care about starting and stopping postfix correctly.
#
# supervisord config snippet for postfix-wrapper:
#
# [program:postfix]
# process_name = postfix
# command = /path/to/postfix-wrapper.sh
# startsecs = 0
# autorestart = false
#

# Init vars
if [[ -z "$SERVICE_POSTFIX_OPTS" ]]; then SERVICE_POSTFIX_OPTS=""; fi

source /opt/docker/bin/config.sh

trap "postfix stop" SIGINT
trap "postfix stop" SIGTERM
trap "postfix reload" SIGHUP

includeScriptDir "/opt/docker/bin/service.d/postfix.d/"

# start postfix
postfix start $SERVICE_POSTFIX_OPTS

# lets give postfix some time to start
sleep 3

# wait until postfix is dead (triggered by trap)
if [[ -f /var/spool/postfix/pid/master.pid ]]; then
    while kill -0 "$(cat /var/spool/postfix/pid/master.pid 2>/dev/null)" &>/dev/null; do
      sleep 5
    done
fi
