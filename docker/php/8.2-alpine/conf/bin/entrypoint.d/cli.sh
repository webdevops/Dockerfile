#!/usr/bin/env bash

#############################################
## Run CLI_SCRIPT from environment variable
#############################################

if [[ -n "${CLI_SCRIPT:-}" ]]; then
    # Pass on the arguments after "cli"
    shift
    if [[ -n "${APPLICATION_USER:-}" ]]; then
        exec gosu "${APPLICATION_USER}" ${CLI_SCRIPT} "$@"
    elif id -u application > /dev/null 2>&1; then
        # APPLICATION_USER not set or empty: its default
        exec gosu application ${CLI_SCRIPT} "$@"
    else
        # Neither APPLICATION_USER nor the user application, as in webdevops/base: run as root
        exec ${CLI_SCRIPT} "$@"
    fi
else
    echo "[ERROR] No CLI_SCRIPT in docker environment defined" >&2
    exit 1
fi
