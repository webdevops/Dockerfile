#!/usr/bin/env bash

#################################################
# General handling
#################################################

log () {
    echo "$1"

}

logError() {
    echo " [ERROR] $1" >&2
}

exitError() {
    echo ""
    echo "$(date) Exit -> $1" >&2
    exit $1
}

retry() {
    local n=1
    local max=5
    local delay=15
    while true; do
        "$@" && break || {
            if [[ $n -lt $max ]]; then
                    ((n++))
                    log "Command failed. Attempt $n/$max:"
                    sleep $delay;
                else
                    exitError "The command has failed after $n attempts."
            fi
        }
    done
}


#################################################
# Background process handling
#################################################

initPidList() {
    unset PID_LIST
    declare -a PID_LIST
}

addBackgroundPidToList() {
    local BG_PID="$!"

echo "$BG_PID"

    if [ "$#" -eq 0 ]; then
        PID_LIST[$BG_PID]="$BG_PID"
    else
        PID_LIST[$BG_PID]="$*"
    fi

}

waitForBackgroundProcesses() {
    local WAIT_BG_RETURN=0

    ## check if pidlist exists
    if [ ${#PID_LIST[@]} -eq 0 ]; then
        echo "No wait processes"
        return
    fi

    while [ 1 ]; do

        for pid in "${!PID_LIST[@]}"; do
            title=${PID_LIST[$pid]}

            # check if pid is finished
            if ! kill -0 "$pid" 2> /dev/null; then
                # get return code
                if wait "$pid" 2> /dev/null; then
                    # success
                    echo " -> Process ${title} finished successfully"
                    unset PID_LIST[$pid]
                else
                    # failed
                    echo " -> Process ${title} FAILED"
                    WAIT_BG_RETURN=1
                    unset PID_LIST[$pid]
                fi

            fi
        done

        if [ "${#PID_LIST[@]}" -eq 0 ]; then

            # check if any subprocess failed
            if [ "$WAIT_BG_RETURN" -ne 0 ]; then
                logError "One or more child processes exited with failure!"
                exitError 1
            fi

            break
        fi

        sleep 3

    done

    initPidList
}


#################################################
# Timing handling
#################################################

timerStart() {
    TIMER_START="$(date +%s)"
}

timerStep() {
    local TIMER_NOW="$(date +%s)"
    local TIMER_DIFF="$(expr ${TIMER_NOW} - ${TIMER_START})"

    if [ "$TIMER_DIFF" -lt 60 ]; then
        echo "${TIMER_DIFF} seconds"
    elif [ "$TIMER_DIFF" -lt 3600 ]; then
        echo "$(expr ${TIMER_DIFF} / 60) minutes"
    else
        echo "$(expr ${TIMER_DIFF} / 60 / 60) hours"
    fi
}

#################################################
# Docker
#################################################

###
 # Push image
 #
 # $1 -> docker container name (eg. webdevops/php)
 # $2 -> docker container tag  (eg. ubuntu-14.04)
 ##
function dockerPushImage() {
    CONTAINER_NAME="$1"
    CONTAINER_TAG="$2"

    docker push "${CONTAINER_NAME}:${CONTAINER_TAG}"
}

###
 # Push image
 #
 # $1 -> base path
 # $2 -> docker container tag  (eg. ubuntu-14.04)
 # $3 -> filter
 ##
function foreachDockerfileInPath() {
   DOCKER_BASE_PATH="$1"
   CALLBACK="$2"
   FILTER="*"

    if [ "$#" -ge 3 ]; then
        FILTER="$3"
    fi

    # build each subfolder as tag
    for DOCKERFILE_PATH in $(find "${DOCKER_BASE_PATH}" -mindepth 1 -maxdepth 1 -type d -name "$FILTER"); do
        # check if there is a Dockerfile
        if [ -f "${DOCKERFILE_PATH}/Dockerfile" ]; then
            DOCKERFILE="${DOCKERFILE_PATH}/Dockerfile"
            TAGNAME=$(basename "${DOCKERFILE_PATH}")
            ${CALLBACK}
        fi
    done
}
