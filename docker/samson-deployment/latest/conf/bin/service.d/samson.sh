#!/usr/bin/env bash

set -o pipefail  ## trace ERR through pipes
set -o errtrace  ## trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

source /opt/docker/bin/config.sh

includeScriptDir "/opt/docker/bin/service.d/samson.d/"

sleep 1

echo "(Re-)Starting Samson"
cd /app/
bin/rake db:migrate
exec bundle exec puma -C ./config/puma.rb -e "$RAILS_ENV"


