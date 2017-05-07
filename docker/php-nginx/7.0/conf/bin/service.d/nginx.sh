#!/usr/bin/env bash

# Init vars
if [[ -z "$SERVICE_NGINX_OPTS" ]]; then SERVICE_NGINX_OPTS=""; fi

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/nginx.d/"

exec /usr/sbin/nginx -g 'daemon off;' $SERVICE_NGINX_OPTS
