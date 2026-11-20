#!/usr/bin/env bash
# Runs Postfix for supervisord (etc/supervisor.d/postfix.conf). "postfix start" puts the master process of Postfix in
# the background, so this script waits until it ends and passes the signals of supervisord on as "postfix stop" and
# "postfix reload". SERVICE_POSTFIX_OPTS holds options of the postfix command, e.g. -v, given before each command.

# Init vars
if [[ -z "$SERVICE_POSTFIX_OPTS" ]]; then SERVICE_POSTFIX_OPTS=""; fi

source /opt/docker/bin/config.sh

trap "postfix $SERVICE_POSTFIX_OPTS stop" SIGINT
trap "postfix $SERVICE_POSTFIX_OPTS stop" SIGTERM
trap "postfix $SERVICE_POSTFIX_OPTS reload" SIGHUP

includeScriptDir "/opt/docker/bin/service.d/postfix.d/"

# A failed start, e.g. because of an unknown option in SERVICE_POSTFIX_OPTS, ends the script with an error before the
# startsecs of postfix.conf, so that supervisord gives up after a few attempts instead of restarting it over and over
if ! postfix $SERVICE_POSTFIX_OPTS start; then
    echo "postfix $SERVICE_POSTFIX_OPTS start failed" >&2
    exit 1
fi

# lets give postfix some time to start
sleep 3

# wait until postfix is dead (triggered by trap)
if [[ -f /var/spool/postfix/pid/master.pid ]]; then
    while kill -0 "$(cat /var/spool/postfix/pid/master.pid 2>/dev/null)" &>/dev/null; do
      sleep 5
    done
fi
