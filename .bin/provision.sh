#!/usr/bin/env bash

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

READLINK_CMD='readlink'

if [ -n "$(which greadlink)" ]; then
    READLINK_CMD='greadlink'
fi

SCRIPT_DIR=$(dirname $($READLINK_CMD -f "$0"))
BASE_DIR=$(dirname "$SCRIPT_DIR")

LOCALSCRIPT_DIR="${BASE_DIR}/_localscripts"
PROVISION_DIR="${BASE_DIR}/_provisioning"

function relativeDir() {
    echo ${1#${BASE_DIR}/}
}

#######################################
# Localscripts
#######################################

function buildLocalscripts() {
    echo " * Building localscripts"

    cd "${LOCALSCRIPT_DIR}"
    rm -f scripts.tar
    tar jcvf scripts.tar *

    find "$BASE_DIR/bootstrap" -type d -depth 1 | while read DOCKER_DIR; do
        if [ -f "${DOCKER_DIR}/Dockerfile" ]; then
            echo "    - $(relativeDir $DOCKER_DIR)"
            cp scripts.tar "${DOCKER_DIR}/scripts.tar"
        fi
    done
}

#######################################
# Provision
#######################################

function clearProvision() {
    DOCKER_CONTAINER="$1"
    DOCKER_FILTER="$2"

    echo " * Clearing provision"
    find "${BASE_DIR}/${DOCKER_CONTAINER}" -type d -depth 1 -iname "${DOCKER_FILTER}" | while read DOCKER_DIR; do
        if [ -f "${DOCKER_DIR}/Dockerfile" ]; then
            echo "    - $(relativeDir $DOCKER_DIR)"
            rm -rf "${DOCKER_DIR}/conf/"
        fi
    done
}

function deployProvision() {
    PROVISION_SUB_DIR="$1"
    DOCKER_CONTAINER="$2"
    DOCKER_FILTER="$3"

    if [ "$DOCKER_FILTER" == "*" ]; then
        echo " * Deploying provision"
    else
        echo " * Deploying provision with filter '$DOCKER_FILTER'"
    fi

    find "${BASE_DIR}/${DOCKER_CONTAINER}" -type d -depth 1 -iname "${DOCKER_FILTER}" | while read DOCKER_DIR; do
        if [ -f "${DOCKER_DIR}/Dockerfile" ]; then
            echo "    - $(relativeDir $DOCKER_DIR)"
            cp -f -r "${PROVISION_DIR}/${PROVISION_SUB_DIR}/" "${DOCKER_DIR}/conf"
        fi
    done
}

#buildLocalscripts

echo "Building provision for webdevops/base..."
clearProvision base '*'
deployProvision base/general base '*'
deployProvision base/centos  base 'centos-*'