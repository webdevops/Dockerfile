#!/usr/bin/env bash

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

source /opt/docker/bin/config.sh

rootCheck "$0"

CONTROL_COMMAND="$1"
shift

case "$CONTROL_COMMAND" in

    ## ------------------------------------------
    ## Service
    ## ------------------------------------------

    "service.enable")
        deprecationNotice " Please use >>docker-service-enable [service]<<"
        docker-service-enable "$1"
        ;;

    "service.disable")
        deprecationNotice " Please use >>docker-service-disable [service]<<"
        docker-service-disable "$1"
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
