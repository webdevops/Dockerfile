#!/usr/bin/env bash

# Init vars
if [[ -z "$SERVICE_VSFTP_OPTS" ]]; then SERVICE_VSFTP_OPTS=""; fi

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/vsftp.d/"

exec vsftpd $SERVICE_VSFTP_OPTS
