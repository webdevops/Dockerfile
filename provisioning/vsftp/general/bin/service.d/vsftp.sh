#!/usr/bin/env bash

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/vsftp.d/"

exec vsftpd
