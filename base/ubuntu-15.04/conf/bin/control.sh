#!/usr/bin/env bash

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

source /opt/docker/bin/config.sh

CONTROL_COMMAND="$1"
shift




case "$CONTROL_COMMAND" in

    ## ------------------------------------------
    ## PROVISION
    ## ------------------------------------------

    ## main roles
    "provision.role")
        provisionRoleAdd "provision.main.bootstrap" "$1"
        provisionRoleAdd "provision.main.entrypoint" "$1"
        ;;

    "provision.role.bootstrap")
        provisionRoleAdd "provision.main.bootstrap" "$1"
        ;;

    "provision.role.entrypoint")
        provisionRoleAdd "provision.main.entrypoint" "$1"
        ;;

    ## startup roles
    "provision.role.startup")
        provisionRoleAdd "provision.startup.bootstrap" "$1"
        provisionRoleAdd "provision.startup.entrypoint" "$1"
        ;;

    "provision.role.startup.bootstrap")
        provisionRoleAdd "provision.startup.bootstrap" "$1"
        ;;

    "provision.role.startup.entrypoint")
        provisionRoleAdd "provision.startup.entrypoint" "$1"
        ;;

    ## startup roles
    "provision.role.finish")
        provisionRoleAdd "provision.finish.bootstrap" "$1"
        provisionRoleAdd "provision.finish.entrypoint" "$1"
        ;;

    "provision.role.finish.bootstrap")
        provisionRoleAdd "provision.finish.bootstrap" "$1"
        ;;

    "provision.role.finish.entrypoint")
        provisionRoleAdd "provision.finish.entrypoint" "$1"
        ;;

    ## ------------------------------------------
    ## Service
    ## ------------------------------------------
    "service.enable")
        SERVICE_FILE="/opt/docker/etc/supervisor.d/$1.conf"
        if [ -f "$SERVICE_FILE" ]; then
            sed -i '/autostart = /c\autostart = true' -- "$SERVICE_FILE"
        else
            echo "[ERROR] Service not found"
            exit 1
        fi
        ;;

    "service.disable")
        SERVICE_FILE="/opt/docker/etc/supervisor.d/$1.conf"
        if [ -f "$SERVICE_FILE" ]; then
            sed -i '/autostart = /c\autostart = false' -- "$SERVICE_FILE"
        else
            echo "[ERROR] Service not found"
            exit 1
        fi
        ;;

    *)
        echo "[ERROR] Invalid controll command: \"${CONTROL_COMMAND}\""
        exit 1
        ;;
esac
