#!/usr/bin/env bash

SED='sed'

[[ `uname` == 'Darwin' ]] && {
	which gsed > /dev/null && {
		SED='gsed'
	} || {
		echo 'ERROR: GNU utils required for Mac. You may use homebrew to install them: brew install coreutils gnu-sed'
		exit 1
	}
}

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

RETRY=5

retry() {
    local n=1
    local max="$RETRY"
    local delay=15m
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


function printLine() {
    printf "${1}%.0s" $(seq 1 50)
    echo
}


#################################################
# Macro functions
#################################################

###
 # Get list of macros in file
 #
 # $1 -> Target file
 ##
function getMacroList() {
    FILE_TARGET="$1"

    grepRegexp='^# <Macro .*>'
    sedRegexp='^# <Macro \([^>]*\)>'

    MARKER_LIST="$(grep -h -E -e "$grepRegexp" "$FILE_TARGET" || exit 0 )"

    if [[ -n "$MARKER_LIST" ]]; then
        echo "$MARKER_LIST" | $SED -e "s/$sedRegexp/\\1/"
    fi
}


###
 # Replace marker area in one file with another file
 #
 # $1 -> Target file
 # $2 -> Marker content file
 # $3 -> Marker name
 ##
function replaceMacro() {
    FILE_TARGET="$1"
    FILE_CONTENT="$2"
    MACRO="$3"

    lead="^# <Macro $MACRO>"
    tail="^# <\/Macro>"

    $SED -i -e "/$lead/,/$tail/{ /$lead/{p; r $FILE_CONTENT
            }; /$tail/p; d }"  "$FILE_TARGET"
}

#################################################
# Background process handling
#################################################

initPidList() {
    unset PID_LIST
    declare -a PID_LIST
    PID_LIST=()

    unset PID_LOG_TITLE
    declare -a PID_LOG_TITLE
    PID_LOG_TITLE=()

    unset PID_LOG_FILE
    declare -a PID_LOG_FILE
    PID_LOG_FILE=()
}

addBackgroundPidToList() {
    local BG_PID="$!"

    case "$#" in
        1)
            PID_LIST[$BG_PID]="$1"
            ;;

        2)
            PID_LIST[$BG_PID]="$1"
            PID_LOG_TITLE[$BG_PID]="$1"
            PID_LOG_FILE[$BG_PID]="$2"
            ;;

        *)
            PID_LIST[$BG_PID]="$BG_PID"
            ;;
    esac
}


ALWAYS_SHOW_LOGS=0
waitForBackgroundProcesses() {
    local WAIT_BG_RETURN=0

    ## check if pidlist exists
    if [[ -z "${PID_LIST[@]:+${PID_LIST[@]}}" ]]; then
        log " -> No background processes found"
        return
    fi

    while [ 1 ]; do

        for pid in "${!PID_LIST[@]}"; do
            title="${PID_LIST[$pid]}"

            # check if pid is finished
            if ! kill -0 "$pid" 2> /dev/null; then
                # get return code
                if wait "$pid" 2> /dev/null; then
                    # success
                    log "    - \"${title}\" finished successfully"
                    unset PID_LIST[$pid]
                else
                    # failed
                    log "    - Process \"${title}\" FAILED"
                    WAIT_BG_RETURN=1
                    unset PID_LIST[$pid]
                fi

            fi
        done

        if [ "${#PID_LIST[@]}" -eq 0 ]; then
            # check if any subprocess failed
            if [ "$WAIT_BG_RETURN" -ne 0 ]; then
                logError "One or more child processes exited with failure!"

                logOutputFromBackgroundProcesses

                exitError 1
            fi

            break
        fi

        sleep 3

    done

    if [[ "$ALWAYS_SHOW_LOGS" -eq 1 ]]; then
        logOutputFromBackgroundProcesses
    fi

    initPidList
}

logOutputFromBackgroundProcesses() {
    ## check if pidlist exists
    if [[ -z "${PID_LOG_FILE[@]:+${PID_LOG_FILE[@]}}" ]]; then
        return
    fi

    echo ""
    echo "Showing logs:"

    for pid in "${!PID_LOG_FILE[@]}"; do
        title="${PID_LOG_TITLE[$pid]}"
        log="${PID_LOG_FILE[$pid]}"

        if [[ -s "$log" ]]; then
            echo "-- begin of \"$title\" log --:"

            cat "$log"
            rm -f -- "$log"

            echo "-- end of \"$title\" log --"

            echo ""
            echo ""
        fi

        unset PID_LOG_TITLE[$pid]
        unset PID_LOG_FILE[$pid]

    done
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

function checkBlacklist() {
    if [[ -s "${BASE_DIR}/BLACKLIST" ]]; then
        echo "$*" | grep -vF "$(cat "${BASE_DIR}/BLACKLIST")"
    else
        echo "$*"
    fi
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
   FILTER=".*"

    if [ "$#" -ge 3 ]; then
        FILTER="$3"
    fi

    if [ -n "${WHITELIST}" ]; then
        for WTAG in $WHITELIST; do
            if [ "${FILTER}" = ".*" ]; then
                FILTER="${WTAG}"
            else
                FILTER="${FILTER}\|${WTAG}"
            fi
        done
    fi

    # build each subfolder as tag
    for DOCKERFILE_PATH in $(find ${DOCKER_BASE_PATH} -mindepth 1 -maxdepth 1 -type d -regex ".*\(${FILTER}\).*"); do
        # check if there is a Dockerfile

        if [ -f "${DOCKERFILE_PATH}/Dockerfile" ]; then
            DOCKERFILE="${DOCKERFILE_PATH}/Dockerfile"
            TAGNAME=$(basename "${DOCKERFILE_PATH}")

            if [[ -n "$(checkBlacklist "${BASENAME}:${TAGNAME}")" ]]; then
               ${CALLBACK}
            fi
        fi
    done
}
