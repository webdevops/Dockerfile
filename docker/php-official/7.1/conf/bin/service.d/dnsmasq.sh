#!/usr/bin/env bash

source /opt/docker/bin/config.sh

DNSMASQ_USER="root"
DNSMASQ_OPTS=""

includeScriptDir "/opt/docker/bin/service.d/dnsmasq.d/"

exec dnsmasq --keep-in-foreground --user="$DNSMASQ_USER" $DNSMASQ_OPTS
