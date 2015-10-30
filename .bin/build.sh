#!/usr/bin/env bash

if [ -z "$FAST" ]; then
    FAST=0
fi

if [ -z "$DEBUG" ]; then
    DEBUG=0
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

    if [ "${FAST}" -eq 1 ]; then
        bash "${WORKDIR}/.bin/buildContainer.sh" "${DOCKERFILE_PATH}" "${CONTAINER_NAME}" "${CONTAINER_TAG}" &
    else
        bash "${WORKDIR}/.bin/buildContainer.sh" "${DOCKERFILE_PATH}" "${CONTAINER_NAME}" "${CONTAINER_TAG}"
    fi

    cd "$WORKDIR"
}

function waitForBuild() {
    if [ "${FAST}" -eq 1 ]; then
        waitForBuildStep
        echo " -> $BASENAME build finished"
    fi
}

function waitForBuildStep() {
    if [ "${FAST}" -eq 1 ]; then
        echo "waiting for build..."
        wait
    fi
}

if [ "${FAST}" -eq 1 ]; then
    echo "Building $BASENAME (FAST MODE)"
else
    echo "Building $BASENAME (SLOW MODE)"
fi

if [ "${DEBUG}" -eq 1 ]; then
    echo "    +++++++ DEBUG MODE +++++++    "
fi

sleep 1


if [ -f "${TARGET}/Dockerfile" ]; then
    TAGNAME="latest"
    buildDockerfile "${TARGET}" "${BASENAME}" "${TAGNAME}"

    waitForBuild
else
    for DOCKERFILE in $TARGET/*; do
        if [ -f "$DOCKERFILE/Dockerfile" ]; then
            TAGNAME=$(basename "$DOCKERFILE")
            buildDockerfile "${DOCKERFILE}" "${BASENAME}" "${TAGNAME}"
            sleep 0.2
        fi
    done

    waitForBuildStep

    if [ -f "${TARGET}/${LATEST}/Dockerfile" ]; then
            DOCKERFILE="${TARGET}/${LATEST}"
            buildDockerfile "${DOCKERFILE}" "${BASENAME}" "latest"
    fi

    waitForBuild
fi

echo ""
echo ""
