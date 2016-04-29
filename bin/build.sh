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
BASE_DIR="$(pwd)"

source "${BASE_DIR}/bin/functions.sh"

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
    local DOCKERFILE_PATH="$1"
    local CONTAINER_NAME="$2"
    local CONTAINER_TAG="$3"

    echo ">> Starting build of ${CONTAINER_NAME}:${CONTAINER_TAG}"

    if [ "${FAST}" -eq 1 ]; then
        LOGFILE="$(mktemp /tmp/docker.build.XXXXXXXXXX)"
        "${BASE_DIR}/bin/retry.sh" "${BASE_DIR}/bin/buildContainer.sh" "${DOCKERFILE_PATH}" "${CONTAINER_NAME}" "${CONTAINER_TAG}" &> "$LOGFILE" &
        addBackgroundPidToList "${CONTAINER_TAG}" "$LOGFILE"
    else
        "${BASE_DIR}/bin/retry.sh" "${BASE_DIR}/bin/buildContainer.sh" "${DOCKERFILE_PATH}" "${CONTAINER_NAME}" "${CONTAINER_TAG}"
    fi

    cd "$BASE_DIR"
}

###
 # Push dockerfile
 #
 # will push one docker container, background mode if FAST mode is active
 #
 # $1 -> dockerfile path       (php/)
 # $2 -> docker container name (eg. webdevops/php)
 # $3 -> docker container tag  (eg. ubuntu-14.04)
 #
 ##
function pushDockerfile() {
    local DOCKERFILE_PATH="$1"
    local CONTAINER_NAME="$2"
    local CONTAINER_TAG="$3"

    echo ">> Starting push of ${CONTAINER_NAME}:${CONTAINER_TAG}"

    if [ "${FAST}" -eq 1 ]; then
        LOGFILE="$(mktemp /tmp/docker.push.XXXXXXXXXX)"
        "${BASE_DIR}/bin/retry.sh" docker push "${CONTAINER_NAME}:${CONTAINER_TAG}" &> "$LOGFILE" &
        addBackgroundPidToList "${CONTAINER_TAG}" "$LOGFILE"
    else
        "${BASE_DIR}/bin/retry.sh" docker push "${CONTAINER_NAME}:${CONTAINER_TAG}"
    fi

    cd "$BASE_DIR"
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
        echo " -> waiting for background processes..."
        waitForBackgroundProcesses
        wait
    fi
}

###
 # Run build task
 #
 ##
function buildTarget() {
    case "$BUILD_MODE" in
        build)
            buildDockerfile "${DOCKERFILE_PATH}" "${BASENAME}" "${TAGNAME}"
            sleep 0.05
            ;;

        push)
            pushDockerfile "${DOCKERFILE_PATH}" "${BASENAME}" "${TAGNAME}"
            sleep 0.05
            ;;
    esac
}

###
 # Run build task for latest container
 #
 ##
function buildTargetLatest() {
    TAGNAME="latest"

    buildTarget
}

###
 # Check if docker image is available
 ##
function checkBuild() {
    if [[ -n "$(docker images -q "${BASENAME}:${TAGNAME}" 2> /dev/null)" ]]; then
        echo " -> Image ${BASENAME}:${TAGNAME} found"
    else
        echo " [ERROR] Docker image '${BASENAME}:${TAGNAME}' not found, build failure!"
        exit 1;
    fi
}

###
 # Check if docker image is available (latest image)
 ##
function checkBuildLatest() {
    TAGNAME="latest"
    checkBuild
}


###############################################################################
# MAIN
###############################################################################

printLine "="

if [ "${FAST}" -eq 1 ]; then
    echo -n "== Docker image $BASENAME (PARALLEL MODE)"
else
    echo -n "== Docker image $BASENAME"
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
# Main
#############################

## Init

initPidList
timerStart

## Build image

echo "Building ${BASENAME}"
## Build each docker tag
foreachDockerfileInPath "docker/${TARGET}" "buildTarget"

# wait for build process
waitForBuildStep

## Build docker tag latest
foreachDockerfileInPath "docker/${TARGET}" "buildTargetLatest" "${LATEST}"

# wait for final build
waitForBuild

logOutputFromBackgroundProcesses

## Check builds (only "build" mode)
case "$BUILD_MODE" in
    build)
        echo ">> Checking built images"
        foreachDockerfileInPath "docker/${TARGET}" "checkBuild"
        foreachDockerfileInPath "docker/${TARGET}" "checkBuildLatest" "${LATEST}"
        ;;
esac

echo ""
echo ">>> Build time: $(timerStep)"
echo ""
