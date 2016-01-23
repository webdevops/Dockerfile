#!/usr/bin/env bash

if [ -z "$FAST" ]; then
    FAST=1
fi

if [ -z "$DEBUG" ]; then
    DEBUG=0
fi

if [ -z "$FORCE" ]; then
    FORCE=0
fi

if [ -z "${BUILD_MODE}" ]; then
    BUILD_MODE="build"
fi

case "$BUILD_MODE" in

    build|push)
        BUILD_MODE="$BUILD_MODE"
        ;;

    *)
        echo "[ERROR] Unknown build mode \"$BUILD_MODE\""
        exit 1
        ;;
esac


set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

TARGET="$1"
BASENAME="$2"
LATEST="$3"
WORKDIR=$(pwd)

source "${WORKDIR}/.bin/functions.sh"

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

    echo ">> Starting build of ${CONTAINER_NAME}:${CONTAINER_TAG}"

    if [ "${FAST}" -eq 1 ]; then
        bash "${WORKDIR}/.bin/buildContainer.sh" "${DOCKERFILE_PATH}" "${CONTAINER_NAME}" "${CONTAINER_TAG}" &
        addBackgroundPidToList "${CONTAINER_TAG}"
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
        echo " -> waiting for background build process..."
        waitForBackgroundProcesses
        wait
    fi
}


###############################################################################
# MAIN
###############################################################################

printLine "="

if [ "${FAST}" -eq 1 ]; then
    echo -n "== Building docker image $BASENAME (PARALLEL MODE)"
else
    echo -n "== Building docker image $BASENAME"
fi

if [ "${DEBUG}" -eq 1 ]; then
    echo -n " >>DEBUG MODE<<"
fi


if [ "${FORCE}" -eq 1 ]; then
    echo -n " >>FORCE MODE<<"
fi

echo ""

printLine "="
echo ""

sleep 0.5


#############################
# Provision
#############################

bash "${WORKDIR}/.bin/provision.sh" "$TARGET"
echo ""

#############################
# Docker build
#############################


initPidList
timerStart

function buildTarget() {
    case "$BUILD_MODE" in
        build)
            buildDockerfile "${DOCKERFILE_PATH}" "${BASENAME}" "${TAGNAME}"
            sleep 0.05
            ;;

        push)
            retry dockerPushImage "${BASENAME}" "${TAGNAME}"
            ;;
    esac
}

function buildTargetLatest() {
    TAGNAME="latest"

    ## build without force
    FORCE=0 buildTarget
}

echo "Building ${BASENAME}"
## Build each docker tag
foreachDockerfileInPath "${TARGET}" "buildTarget"

# wait for build process
waitForBuildStep

## Build docker tag latest
foreachDockerfileInPath "${TARGET}" "buildTargetLatest" "${LATEST}"

# wait for final build
waitForBuild

echo ""
echo ">>> Build time: $(timerStep)"

echo ""
echo ""
