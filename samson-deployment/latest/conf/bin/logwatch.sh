#!/usr/bin/env bash

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

sleep 0.5

if [ -p "$2" ]; then
    sed --unbuffered -e "s/^/\[$1\] /" -- "$2"
else
    tail --lines=0 --follow=name --quiet "$2" | sed --unbuffered -e "s/^/\[$1\] /"
fi
