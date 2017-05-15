#!/usr/bin/env bash

mkdir -p /var/run/hhvm/
chown -R "${APPLICATION_USER}:${APPLICATION_GROUP}" /var/run/hhvm/
chmod 0700 /var/run/hhvm/

# Setup hhvm configuration
go-replace --mode=lineinfile \
    -s 'hhvm.server.fix_path_info' -r 'hhvm.server.fix_path_info = true' \
    -s 'hhvm.log.file' -r 'hhvm.log.file = /docker.stdout' \
    -- /etc/hhvm/php.ini
