#!/usr/bin/env bash

#############################################
## Supervisord (start daemons)
#############################################

## Start services
exec /opt/docker/bin/service.d/supervisor.sh

