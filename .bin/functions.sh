#!/usr/bin/env bash

#################################################
# General handling
#################################################


logError() {
    echo " [ERROR] $1"
}

exitError() {
    echo ""
    echo "$(date) Exit -> $1"
    exit $1
}

#################################################
# Background process handling
#################################################

initPidList() {
    unset PID_LIST
    declare -a PID_LIST
}

addBackgroundPidToList() {
    BG_PID="$!"

    if [ "$#" -eq 0 ]; then
        PID_LIST[$BG_PID]="$BG_PID"
    else
        PID_LIST[$BG_PID]="$*"
    fi
}

waitForBackgroundProcesses() {
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
                    exit 1
                fi

            fi
        done

        if [ "${#PID_LIST[@]}" -eq 0 ]; then
            break
        fi

        sleep 3

    done

    initPidList
}
