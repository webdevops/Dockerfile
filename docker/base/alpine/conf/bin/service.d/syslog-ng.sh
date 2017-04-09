#!/usr/bin/env bash
set -e

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/syslog-ng.d/"

exec syslog-ng -F --no-caps -p  /var/run/syslog-ng.pid $SYSLOGNG_OPTS
