#!/usr/bin/env bash

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

source /opt/docker/bin/config.sh

rootCheck

CONTROL_COMMAND="$1"
shift

case "$CONTROL_COMMAND" in

    ## ------------------------------------------
    ## PROVISION
    ## ------------------------------------------

    ## main roles
    "provision.role")
        provisionRoleAdd "provision.main.bootstrap"  "$1"
        provisionRoleAdd "provision.main.onbuild"    "$1"
        provisionRoleAdd "provision.main.entrypoint" "$1"
        ;;

    "provision.role.bootstrap")
        provisionRoleAdd "provision.main.bootstrap" "$1"
        ;;

    "provision.role.onbuild")
        provisionRoleAdd "provision.main.onbuild" "$1"
        ;;

    "provision.role.entrypoint")
        provisionRoleAdd "provision.main.entrypoint" "$1"
        ;;

    ## startup roles
    "provision.role.startup")
        provisionRoleAdd "provision.startup.bootstrap"  "$1"
        provisionRoleAdd "provision.startup.onbuild"    "$1"
        provisionRoleAdd "provision.startup.entrypoint" "$1"
        ;;

    "provision.role.startup.bootstrap")
        provisionRoleAdd "provision.startup.bootstrap" "$1"
        ;;

    "provision.role.startup.onbuild")
        provisionRoleAdd "provision.startup.onbuild" "$1"
        ;;

    "provision.role.startup.entrypoint")
        provisionRoleAdd "provision.startup.entrypoint" "$1"
        ;;

    ## startup roles
    "provision.role.finish")
        provisionRoleAdd "provision.finish.bootstrap"  "$1"
        provisionRoleAdd "provision.finish.onbuild"    "$1"
        provisionRoleAdd "provision.finish.entrypoint" "$1"
        ;;

    "provision.role.finish.bootstrap")
        provisionRoleAdd "provision.finish.bootstrap" "$1"
        ;;

    "provision.role.finish.onbuild")
        provisionRoleAdd "provision.finish.onbuild" "$1"
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
            echo "[ERROR] Service '${1}' not found (tried ${SERVICE_FILE})"
            exit 1
        fi
        ;;

    "service.disable")
        SERVICE_FILE="/opt/docker/etc/supervisor.d/$1.conf"
        if [ -f "$SERVICE_FILE" ]; then
            sed -i '/autostart = /c\autostart = false' -- "$SERVICE_FILE"
        else
            echo "[ERROR] Service '${1}' not found (tried ${SERVICE_FILE})"
            exit 1
        fi
        ;;

    ## ------------------------------------------
    ## Version
    ## ------------------------------------------

    "version.get")
        cat /opt/docker/VERSION
        ;;

    "version.require.min")
        EXPECTED_VERSION="$1"
        CURRENT_VERSION="$(cat /opt/docker/VERSION)"
        if [ "$CURRENT_VERSION" -lt "$EXPECTED_VERSION" ]; then
            echo "-----------------------------------------------------------"
            echo "--- This docker image is not up2date!"
            echo "--- "
            echo "--- Version expected min: $EXPECTED_VERSION"
            echo "--- Version current: $CURRENT_VERSION"
            echo "--- "
            echo "--- Run 'docker pull <imagename>' to update image"
            echo "-----------------------------------------------------------"
            exit 1
        fi
        ;;

    "version.require.max")
        EXPECTED_VERSION="$1"
        CURRENT_VERSION="$(cat /opt/docker/VERSION)"
        if [ "$CURRENT_VERSION"  -gt "$EXPECTED_VERSION" ]; then
            echo "-----------------------------------------------------------"
            echo "--- This docker image is too new!"
            echo "--- "
            echo "--- Version expected max: $EXPECTED_VERSION"
            echo "--- Version current: $CURRENT_VERSION"
            echo "-----------------------------------------------------------"
            exit 1
        fi
        ;;


    "buildtime.get")
        cat /opt/docker/BUILDTIME
        ;;

    *)
        echo "[ERROR] Invalid controll command: \"${CONTROL_COMMAND}\""
        exit 1
        ;;
esac
