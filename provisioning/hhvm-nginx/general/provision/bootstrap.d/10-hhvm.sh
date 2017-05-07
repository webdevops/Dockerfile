#!/usr/bin/env bash

# Setup listeing only to localhost
go-replace --mode=lineinfile \
    -s 'hhvm.server.ipl' -r 'hhvm.server.ip = 127.0.0.1' \
    -- /etc/hhvm/server.ini
