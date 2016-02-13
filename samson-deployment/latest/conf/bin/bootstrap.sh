#!/usr/bin/env bash

BOOTSTRAP_MODE="bootstrap"

if [ -n "$1" ]; then
    BOOTSTRAP_MODE="$1"
fi

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

source /opt/docker/bin/config.sh

rootCheck

# Save the buildtime
date +%s > /opt/docker/BUILDTIME

# Make all scripts executable
find /opt/docker/bin/ -type f -iname '*.sh' -print0 | xargs --no-run-if-empty -0 chmod +x


case "$BOOTSTRAP_MODE" in
    ###################################
    # When container will be build next time
    ###################################
    "onbuild")
        # Init and run bootstrap system
        runProvisionOnBuild
        ;;

    ###################################
    # When container is build this time
    ###################################
    "bootstrap")
        # Init and run bootstrap system
        runProvisionBootstrap
        ;;

    *)
        echo "[ERROR] Bootstrap mode '$BOOTSTRAP_MODE' not defined"
        exit 1
        ;;

esac

