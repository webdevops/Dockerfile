#!/usr/bin/env bash

if [ -z "$RETRY" ]; then
    RETRY=1
fi

if [ -z "$RETRY_DELAY" ]; then
    RETRY_DELAY="1m"
fi

RETURN_CODE=0

MKTEMP='mktemp'

[[ `uname` == 'Darwin' ]] && {
	which greadlink > /dev/null && {
		MKTEMP='gmktemp'
	} || {
		echo 'ERROR: GNU utils required for Mac. You may use homebrew to install them: brew install coreutils'
		exit 1
	}
}


if [[ "$RETRY" -le 1 ]]; then
    exec "$@"
fi


LOGFILE="$($MKTEMP --tmpdir retry.XXXXXXXXXX)"

retry() {
    local n=0

    until [[ "$n" -ge "$RETRY" ]]; do
        # Reset logfile for this try
        echo > "$LOGFILE"

        RETURN_CODE="0"
        "$@" && break || {
            ((n++))
            echo ""
            echo " [WARNING] Command failed. Retry now ... $n/$RETRY:"
            echo ""
            echo ""
            RETURN_CODE=1
            sleep "$RETRY_DELAY";
        }
    done

    if [[ "$RETURN_CODE" -ne 0 ]]; then
        echo " [ERROR] The command has failed after $n attempts."
    fi
}

retry "$@" &> "$LOGFILE"

if [[ "$RETURN_CODE" -ne 0 ]]; then
    cat "$LOGFILE"
fi
rm -f "$LOGFILE"

exit "$RETURN_CODE"
