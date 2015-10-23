#!/usr/bin/env bash

set -e
trap 'echo sigterm ; exit' SIGTERM
trap 'echo sigkill ; exit' SIGKILL

TASK="$1"

source /opt/docker/bin/config.sh

if [ "$TASK" == "supervisord" -o "$TASK" == "noop" ]; then
    # Visible provisioning
    initEntrypoint
else
    # Hidden provisioning
    initEntrypoint  > /dev/null
fi

#############################
## COMMAND
#############################

case "$TASK" in

    #############################################
    ## Supervisord (start daemons)
    supervisord)
        ## Start services
        startSupervisord
        ;;

    #############################################
    # Endless noop loop
    noop)
        while true; do
            sleep 1
        done
        ;;

    #############################################
    ## Root shell
    root)
        if [ "$#" -eq 1 ]; then
            ## No command, fall back to interactive shell
            exec bash
        else
            ## Exec root command
            shift
            exec "$@"
        fi
        ;;

    #############################################
    ## Defined cli script
    cli)
        if [ -n "${CLI_SCRIPT}" ]; then
            if [ -n "$EFFECTIVE_USER" ]; then
                # Run as EFFECTIVE_USER
                shift
                exec sudo -H -E -u "${EFFECTIVE_USER}" ${CLI_SCRIPT} "$@"
            else
                # Run as root
                exec ${CLI_SCRIPT} "$@"
            fi
        else
            echo "[ERROR] No CLI_SCRIPT in docker-env.yml defined"
            exit 1
        fi
        ;;

    #############################################
    ## All other commands
    *)
        if [ -n "$EFFECTIVE_USER" ]; then
            # Run as EFFECTIVE_USER
            exec sudo -H -E -u "${EFFECTIVE_USER}" "$@"
        else
            # Run as root
            exec "$@"
        fi
        ;;

esac