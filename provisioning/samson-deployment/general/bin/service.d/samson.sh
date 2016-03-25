#!/bin/bash

set -o pipefail  ## trace ERR through pipes
set -o errtrace  ## trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

#############################
# Vacuum database
#############################

find /app/db/ -iname "*.sqlite3" | while read SQLITE_DATABSE; do
    echo "Running VACUUM on $SQLITE_DATABSE"
    sqlite3 "$SQLITE_DATABSE" "VACUUM;"
done

sleep 1

echo "(Re-)Starting Samson"
cd /app/
bin/rake db:migrate
exec bundle exec puma -C ./config/puma.rb -e "$RAILS_ENV"
