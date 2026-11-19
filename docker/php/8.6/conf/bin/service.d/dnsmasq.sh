#!/usr/bin/env bash

# Init vars
if [[ -z "$SERVICE_DNSMASQ_OPTS" ]]; then SERVICE_DNSMASQ_OPTS=""; fi
if [[ -z "$SERVICE_DNSMASQ_USER" ]]; then SERVICE_DNSMASQ_USER="root"; fi

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/dnsmasq.d/"

exec dnsmasq --keep-in-foreground --user="$SERVICE_DNSMASQ_USER" $SERVICE_DNSMASQ_OPTS
