#!/usr/bin/env bash

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/dnsmasq.d/"

exec dnsmasq --keep-in-foreground
