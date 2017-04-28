#!/usr/bin/env bash

# Init vars
if [[ -z "$SERVICE_PHPFPM_OPTS" ]]; then SERVICE_PHPFPM_OPTS=""; fi

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/php-fpm.d/"

exec /usr/local/bin/php-fpm --nodaemonize $SERVICE_PHPFPM_OPTS
