#!/usr/bin/env bash

# Init vars
if [[ -z "$SERVICE_HHVM_OPTS" ]]; then SERVICE_HHVM_OPTS=""; fi

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/hhvm.d/"

if [[ -z "$CONTAINER_UID" ]]; then
    CONTAINER_UID="application"
fi

exec gosu "${CONTAINER_UID}":"${CONTAINER_UID}" /usr/bin/hhvm --mode server -vServer.Type=fastcgi -vServer.Port=9000 -vServer.AllowRunAsRoot=1 $SERVICE_HHVM_OPTS
