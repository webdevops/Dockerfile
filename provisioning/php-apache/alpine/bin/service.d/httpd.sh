#!/usr/bin/env bash

# Init vars
if [[ -z "$SERVICE_APACHE_OPTS" ]]; then SERVICE_APACHE_OPTS=""; fi

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/httpd.d/"

# Apache does not start while its pid file from an earlier run names a running process
rm -f /run/apache2/httpd.pid

# httpd itself, not apachectl, which would run it as a child: supervisord has to stop and restart httpd
exec /usr/sbin/httpd -DFOREGROUND $SERVICE_APACHE_OPTS
