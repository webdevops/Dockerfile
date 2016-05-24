#!/usr/bin/env bash

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/dovecot.d/"

exec /usr/sbin/dovecot -F
