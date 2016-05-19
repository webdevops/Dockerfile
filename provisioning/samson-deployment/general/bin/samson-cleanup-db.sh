#!/bin/bash

set -o pipefail  ## trace ERR through pipes
set -o errtrace  ## trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

if [[ -d "/storage/db/" ]]; then
    find /storage/db/ -iname "*.sqlite3" | while read SQLITE_DATABSE; do
        if [[ "$SQLITE_CLEANUP_DAYS" -gt 0 ]]; then
            echo "Cleanup old deployment job logs (older than ${SQLITE_CLEANUP_DAYS} days)"
            sqlite3 "$SQLITE_DATABSE" "UPDATE jobs SET output = '# Log cleared' WHERE created_at <= date('now', '-${SQLITE_CLEANUP_DAYS} day');"  || echo " -> ERROR: Cleanup failed"
        fi

        echo "Running VACUUM on $SQLITE_DATABSE"
        sqlite3 "$SQLITE_DATABSE" "VACUUM;" || echo " -> ERROR: VACUUM failed"
    done
fi
