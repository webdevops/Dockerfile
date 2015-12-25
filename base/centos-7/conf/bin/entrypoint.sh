#!/usr/bin/env bash

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

trap 'echo sigterm ; exit' SIGTERM
trap 'echo sigkill ; exit' SIGKILL

TASK="$1"

source /opt/docker/bin/config.sh

if [ "$TASK" == "supervisord" -o "$TASK" == "noop" ]; then
    # Visible provisioning
    runProvisionEntrypoint
else
    # Hidden provisioning
    runProvisionEntrypoint  > /dev/null
fi

#############################
## COMMAND
#############################

case "$TASK" in

    #############################################
    ## Supervisord (start daemons)
    #############################################
    supervisord)
        ## Start services
        startSupervisord
        ;;

    #############################################
    # Endless noop loop
    #############################################
    noop)
        while true; do
            sleep 1
        done
        ;;

    #############################################
    ## Root shell
    #############################################
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
    #############################################
    cli)
        if [ -n "${CLI_SCRIPT}" ]; then
            if [ -n "APPLICATION_USER" ]; then
                # Run as EFFECTIVE_USER
                shift
                exec sudo -H -E -u "${APPLICATION_USER}" ${CLI_SCRIPT} "$@"
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
    #############################################
    *)
        if [ -n "${APPLICATION_USER}" ]; then
            # Run as APPLICATION_USER
            exec sudo -H -E -u "${APPLICATION_USER}" "$@"
        else
            # Run as root
            exec "$@"
        fi
        ;;

esac
