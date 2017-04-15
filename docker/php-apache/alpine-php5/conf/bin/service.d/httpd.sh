#!/usr/bin/env bash
set -e
source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/httpd.d/"

# Apache gets grumpy about PID files pre-existing
rm -f /var/run/httpd/httpd.pid

exec /usr/sbin/apachectl -DFOREGROUND
