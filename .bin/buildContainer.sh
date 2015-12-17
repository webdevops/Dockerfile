#!/usr/bin/env bash

LOGFILE=""

if [ -z "$DEBUG" ]; then
    DEBUG=0
fi

if [ -z "$FORCE" ]; then
    FORCE=0
fi

if [ -z "$DOCKER_OPTS" ]; then
    DOCKER_OPTS=""
fi

set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable

DOCKERFILE_PATH="$1"
CONTAINER_NAME="$2"
CONTAINER_TAG="$3"

###############################################################################
# MAIN
###############################################################################

if [ "$FORCE" -eq 1 ]; then
    DOCKER_OPTS="$DOCKER_OPTS --no-cache"
fi


cd "$DOCKERFILE_PATH"

if [ "$DEBUG" -eq 0 ]; then
    # Background mode, write all logs into tmpfile
    LOGFILE=$(mktemp /tmp/docker.build.XXXXXXXXXX)
    docker build $DOCKER_OPTS -t "${CONTAINER_NAME}:${CONTAINER_TAG}" . &> "$LOGFILE"
    DOCKER_BUILD_RET="$?"
else
    # Foreground mode, write all logs to STDOUT
    docker build $DOCKER_OPTS -t "${CONTAINER_NAME}:${CONTAINER_TAG}" .
    DOCKER_BUILD_RET="$?"
fi


if [ "$DOCKER_BUILD_RET" -ne 0 ]; then
    # docker build failed
    # output the logfile
    if [ -n "$LOGFILE" ]; then
        # LOGFILE is set so the build was done in background mode
        # -> output logfile content

        cat "$LOGFILE"
    fi

    # output error message
    echo ""
    echo ""
    echo "-----------------------------------------------------------"
    echo " --- BUILD FAILURE  -> ${CONTAINER_NAME}:${CONTAINER_TAG}"
    echo "-----------------------------------------------------------"
fi

if [ -n "$LOGFILE" ]; then
    # remove tmpfile (logfile)
    rm -f -- "$LOGFILE"
fi

# exit with docker build return code
exit "$DOCKER_BUILD_RET"
