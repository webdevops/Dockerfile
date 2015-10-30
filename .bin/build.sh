#!/usr/bin/env bash

if [ -z "$FAST" ]; then
    FAST=0
fi

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

TARGET="$1"
BASENAME="$2"
LATEST="$3"
WORKDIR=$(pwd)

function buildDockerfile() {
    DOCKERFILE_PATH="$1"
    CONTAINER_NAME="$2"
    CONTAINER_TAG="$3"

    echo " Starting build of ${CONTAINER_NAME}:${CONTAINER_TAG} ..."
    cd "$DOCKERFILE_PATH"

    if [ "${FAST}" -eq 1 ]; then
        docker build  -t "${CONTAINER_NAME}:${CONTAINER_TAG}" . &
    else
        docker build  -t "${CONTAINER_NAME}:${CONTAINER_TAG}" .
    fi

    echo ""
    echo " ---> ${CONTAINER_NAME}:${CONTAINER_TAG} <---"
    echo ""

    cd "$WORKDIR"
}


if [ "${FAST}" -eq 1 ]; then
    echo "Building $BASENAME< (FAST MODE)"
else
    echo "Building $BASENAME< (SLOW MODE)"
fi

sleep 1


if [ -f "${TARGET}/Dockerfile" ]; then
    TAGNAME="latest"
    buildDockerfile "${TARGET}" "${BASENAME}" "${TAGNAME}"
else
    for DOCKERFILE in $TARGET/*; do
        if [ -f "$DOCKERFILE/Dockerfile" ]; then
            TAGNAME=$(basename "$DOCKERFILE")
            buildDockerfile "${DOCKERFILE}" "${BASENAME}" "${TAGNAME}"
            sleep 1
        fi
    done

    if [ -f "${TARGET}/${LATEST}/Dockerfile" ]; then
            DOCKERFILE="${TARGET}/${LATEST}"
            buildDockerfile "${DOCKERFILE}" "${BASENAME}" "latest"
    fi
fi

if [ "${FAST}" -eq 1 ]; then
    wait
fi
