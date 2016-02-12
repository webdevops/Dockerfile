#!/usr/bin/env bash

if [ -z "$RETRY_COUNT" ]; then
    RETRY_COUNT=5
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


if [[ "$RETRY_COUNT" -le 1 ]]; then
    exec "$@"
fi


LOGFILE="$($MKTEMP --tmpdir retry.XXXXXXXXXX)"

exec 1>"$LOGFILE" 2>&1

retry() {
    local n=0

    until [[ "$n" -ge "$RETRY_COUNT" ]]; do
        RETURN_CODE="0"
        "$@" && break || {
            ((n++))
            echo ""
            echo " [WARNING] Command failed. Retry now ... $n/$RETRY_COUNT:"
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

retry "$@"

exec &>/dev/tty
if [[ "$RETURN_CODE" -ne 0 ]]; then
    cat "$LOGFILE"
fi
rm -f "$LOGFILE"

exit "$RETURN_CODE"
