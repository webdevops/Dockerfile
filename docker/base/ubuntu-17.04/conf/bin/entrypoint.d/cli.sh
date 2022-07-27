#!/usr/bin/env bash

#############################################
## Run CLI_SCRIPT from environment variable
#############################################

if [ -n "${CLI_SCRIPT}" ]; then
    if [ -n "${CONTAINER_UID}" ]; then
        # Run as EFFECTIVE_USER
        shift
        exec gosu "${CONTAINER_UID}" "${CLI_SCRIPT}" "$@"
    else
        # Run as root
        exec "${CLI_SCRIPT}" "$@"
    fi
else
    echo "[ERROR] No CLI_SCRIPT in in docker environment defined"
    exit 1
fi
