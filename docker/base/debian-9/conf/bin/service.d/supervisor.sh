#!/usr/bin/env bash

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/supervisor.d/"

exec supervisord -c /opt/docker/etc/supervisor.conf --logfile /dev/null --pidfile /dev/null --user root
