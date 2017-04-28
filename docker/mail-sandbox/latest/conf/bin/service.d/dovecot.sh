#!/usr/bin/env bash

# Init vars
if [[ -z "$SERVICE_DOVECOT_OPTS" ]]; then SERVICE_DOVECOT_OPTS=""; fi

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/dovecot.d/"

exec /usr/sbin/dovecot -F $SERVICE_DOVECOT_OPTS
