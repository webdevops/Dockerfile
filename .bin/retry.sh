#!/usr/bin/env bash

if [ -z "$RETRY_COUNT" ]; then
    RETRY_COUNT=5
fi

if [ -z "$RETRY_DELAY" ]; then
    RETRY_DELAY="1m"
fi

RETURN_CODE=0

retry() {
    local n=1

    until [[ "$n" -ge "$RETRY_COUNT" ]]; do
        RETURN_CODE="0"
        "$@" && break || {
            echo ""
            echo " [WARNING] Command failed. Attempt $n/$RETRY_COUNT:"
            echo ""
            echo ""
            ((n++))
            RETURN_CODE=1
            sleep 1;
        }
    done

    if [[ "$RETURN_CODE" -ne 0 ]]; then
        echo " [ERROR] The command has failed after $n attempts."
    fi

}

retry $*

exit "$RETURN_CODE"
