#!/usr/bin/env bash

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

if [ "$FORCE" -eq 1 ]; then
    DOCKER_OPTS="$DOCKER_OPTS --no-cache"
fi


cd "$DOCKERFILE_PATH"

if [ "$DEBUG" -eq 0 ]; then
    LOGFILE=$(mktemp /tmp/docker.build.XXXXXXXXXX)
    docker build $DOCKER_OPTS -t "${CONTAINER_NAME}:${CONTAINER_TAG}" . &> "$LOGFILE"
    DOCKER_BUILD_RET="$?"
else
    docker build $DOCKER_OPTS -t "${CONTAINER_NAME}:${CONTAINER_TAG}" .
    DOCKER_BUILD_RET="$?"
fi


if [ "$DOCKER_BUILD_RET" -ne 0 ]; then
    if [ "$DEBUG" -eq 0 ]; then
        cat "$LOGFILE"
        rm -f -- "$LOGFILE"
    fi

    exit "$DOCKER_BUILD_RET"
fi

exit 0
