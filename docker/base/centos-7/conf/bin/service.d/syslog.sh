#!/usr/bin/env bash

# Init vars
if [[ -z "$SERVICE_SYSLOG_OPTS" ]]; then SERVICE_SYSLOG_OPTS=""; fi

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/syslog.d/"

exec go-syslogd $SYSLOG_OPTS $SERVICE_SYSLOG_OPTS
