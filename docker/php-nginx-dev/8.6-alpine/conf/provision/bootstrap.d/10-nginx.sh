#!/usr/bin/env bash

# Disable sendfile for nginx (eg. nfs usage)
go-replace --mode=lineinfile --regex \
    -s '^[\s#]*(sendfile)' -r 'sendfile off;' \
    --  /etc/nginx/nginx.conf
