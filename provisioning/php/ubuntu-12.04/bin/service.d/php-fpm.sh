#!/usr/bin/env bash

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/php.d/"

exec /usr/sbin/php5-fpm
