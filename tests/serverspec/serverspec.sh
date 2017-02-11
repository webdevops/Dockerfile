#!/usr/bin/env bash

set -o pipefail  ## trace ERR through pipes
set -o errtrace  ## trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

PARAM_SPEC_FILE="$1"
PARAM_DOCKER_IMAGE="$2"
PARAM_SPEC_CONF="$3"

# LOGFILE="${PARAM_DOCKER_IMAGE//:/_}"
# LOGFILE="${PARAM_DOCKER_IMAGE//\//_}"
# LOGFILE="${SCRIPT_DIR}/logs/${LOGFILE}.log"

exec bundle exec rake spec["$PARAM_SPEC_FILE","$PARAM_DOCKER_IMAGE","$PARAM_SPEC_CONF"]

