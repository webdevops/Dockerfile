#!/usr/bin/env bash

if [ -z "$FAST" ]; then
    FAST=1
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

###
 # Build dockerfile
 #
 # will build one docker container, background mode if FAST mode is active
 #
 # $1 -> dockerfile path       (php/)
 # $2 -> docker container name (eg. webdevops/php)
 # $3 -> docker container tag  (eg. ubuntu-14.04)
 #
 ##
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

###
 # Wait for build
 #
 # will wait for parallel build processes (only FAST mode)
 #
 ##
function waitForBuild() {
    if [ "${FAST}" -eq 1 ]; then
        waitForBuildStep
        echo " -> $BASENAME build finished"
    fi
}

###
 # Wait for build
 #
 # will wait for parallel build processes (only FAST mode)
 #
 ##
function waitForBuildStep() {
    if [ "${FAST}" -eq 1 ]; then
        echo "waiting for build..."
        wait
    fi
}


###############################################################################
# MAIN
###############################################################################

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
    # If target is only a simple container without sub folders
    # just build it as single container -> latest tag

    TAGNAME="latest"
    buildDockerfile "${TARGET}" "${BASENAME}" "${TAGNAME}"

    waitForBuild
else
    # Target is a multiple tag container, each sub directory name is
    # the name of the docker image tag

    # build each subfolder as tag
    for DOCKERFILE in $TARGET/*; do
        if [ -f "$DOCKERFILE/Dockerfile" ]; then
            TAGNAME=$(basename "$DOCKERFILE")
            buildDockerfile "${DOCKERFILE}" "${BASENAME}" "${TAGNAME}"
            sleep 0.2
        fi
    done

    # wait for build process
    waitForBuildStep

    # build latest tag
    if [ -f "${TARGET}/${LATEST}/Dockerfile" ]; then
            DOCKERFILE="${TARGET}/${LATEST}"
            buildDockerfile "${DOCKERFILE}" "${BASENAME}" "latest"
    fi

    # wait for final build
    waitForBuild
fi

echo ""
echo ""
