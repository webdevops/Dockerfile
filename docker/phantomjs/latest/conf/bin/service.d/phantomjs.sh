#!/bin/sh

set -e

ARGS="--webdriver=${PHANTOMJS_PORT}"

if [ ${PHANTOMJS_IGNORE_SSL_ERRORS} = 'true' ]
then
    ARGS="$ARGS --ignore-ssl-errors=true "
fi

if [ ${PHANTOMJS_LOAD_IMAGES} = 'true' ]
then
    ARGS="$ARGS --load-images=true "
fi

ARGS="$ARGS --disk-cache=true --cookies-file=/tmp/cookies.txt --max-disk-cache-size=524288 --local-storage-path=/var/phantomjs_storage"

phantomjs ${ARGS}