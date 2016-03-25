#!/usr/bin/env bash

if [ -n "${APPLICATION_USER}" ]; then
    # Run as APPLICATION_USER
    exec sudo -H -E -u "${APPLICATION_USER}" "$@"
else
    # Run as root
    exec "$@"
fi
