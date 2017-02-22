#!/usr/bin/env bash

#############################################
## Supervisord (start daemons)
#############################################

rootCheck "supervisord"

## Start services
exec /opt/docker/bin/service.d/supervisor.sh

