#!/usr/bin/env bash

# Init vars
if [[ -z "$SERVICE_APACHE_OPTS" ]]; then SERVICE_APACHE_OPTS=""; fi

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/httpd.d/"

# Apache gets grumpy about PID files pre-existing
rm -f /var/run/httpd/httpd.pid

exec /usr/sbin/apachectl -DFOREGROUND $SERVICE_APACHE_OPTS
