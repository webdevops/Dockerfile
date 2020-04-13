#!/usr/bin/env bash

# Init vars
if [[ -z "$SERVICE_CRON_OPTS" ]]; then SERVICE_CRON_OPTS=""; fi

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/cron.d/"

exec /usr/sbin/crond -f $SERVICE_CRON_OPTS
