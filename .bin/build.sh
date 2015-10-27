#!/usr/bin/env bash

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

TARGET="$1"
BASENAME="$2"

WORKDIR=$(pwd)


function buildDockerfile() {
    DOCKERFILE_PATH="$1"
    CONTAINER_NAME="$2"
    CONTAINER_TAG="$3"

    echo " Starting build of ${CONTAINER_NAME}:${CONTAINER_TAG} ..."
    cd "$DOCKERFILE_PATH"
    docker build  -t "${CONTAINER_NAME}:${CONTAINER_TAG}" .

    echo ""
    echo " ---> ${CONTAINER_NAME}:${CONTAINER_TAG} <---"
    echo ""

    cd "$WORKDIR"
}


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
fi
