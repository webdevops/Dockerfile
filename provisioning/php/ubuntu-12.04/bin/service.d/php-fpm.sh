#!/usr/bin/env bash

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/php-fpm.d/"

exec /usr/local/bin/php-fpm
