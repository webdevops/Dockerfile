#!/usr/bin/env bash

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/hhvm.d/"

if [[ "$CONTAINER_UID" == 0 ]]; then
    CONTAINER_UID="application"
then

exec /usr/bin/hhvm --mode server -vServer.Type=fastcgi -vServer.Port=9000 --user "${CONTAINER_UID}"
