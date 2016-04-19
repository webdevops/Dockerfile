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

# TODO: currently only registering roles
for role in "$@"; do
    /opt/docker/bin/control.sh provision.role.bootstrap "$role"
done
