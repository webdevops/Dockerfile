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
    "provision.role")
        mkdir -p -- "${PROVISION_REGISTRY_PATH}"
        touch -- "${PROVISION_REGISTRY_PATH}/provision.bootstrap"
        touch -- "${PROVISION_REGISTRY_PATH}/provision.entrypoint"

        echo "$1" >> "${PROVISION_REGISTRY_PATH}/provision.bootstrap"
        echo "$1" >> "${PROVISION_REGISTRY_PATH}/provision.entrypoint"
        ;;

    "provision.role.bootstrap")
        mkdir -p -- "${PROVISION_REGISTRY_PATH}"
        touch -- "${PROVISION_REGISTRY_PATH}/provision.bootstrap"

        echo "$1" >> "${PROVISION_REGISTRY_PATH}/provision.bootstrap"
        ;;

    "provision.role.entrypoint")
        mkdir -p -- "${PROVISION_REGISTRY_PATH}"
        touch -- "${PROVISION_REGISTRY_PATH}/provision.entrypoint"

        echo "$1" >> "${PROVISION_REGISTRY_PATH}/provision.entrypoint"
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
        echo "[ERROR] Invalid command"
        exit 1
        ;;
esac
