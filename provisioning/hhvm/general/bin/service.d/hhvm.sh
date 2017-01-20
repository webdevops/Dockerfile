#!/usr/bin/env bash

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/hhvm.d/"

if [[ -z "$CONTAINER_UID" ]]; then
    CONTAINER_UID="application"
fi

exec /usr/bin/hhvm --mode server -vServer.Type=fastcgi -vServer.Port=9000 --user "${CONTAINER_UID}"
